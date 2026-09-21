<?php

namespace App\Modules\Group\Actions;

use Throwable;
use App\Services\Idp\IdpUser;
use Illuminate\Support\Facades\DB;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Cache;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use App\Providers\IdpServiceProvider;
use App\Modules\Group\Models\GroupMember;
use App\Services\Idp\Contracts\IdpClient;
use Lorisleiva\Actions\Concerns\AsController;

/**
 * Typeahead candidates for adding a group member: GPM people matching the
 * typed name/email, merged with identities from the external identity
 * provider that are not yet GPM users.
 *
 * Merge rules, in order: identities already linked to a user are dropped;
 * identities whose address belongs to a user are dropped; identities whose
 * address matches a person without an account are folded into that person's
 * row (has_idp_identity); what remains is one row per identity address.
 * An unreachable IdP degrades to GPM-only results with idp_available=false.
 */
class MemberCandidatesList
{
    use AsController;

    private const FILTERS = ['first_name', 'last_name', 'email'];

    private const GPM_LIMIT = 20;

    public function __construct(private IdpClient $client)
    {
    }

    public function handle(Group $group, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $enabled = IdpServiceProvider::enabled();
        $available = $enabled;

        $people = $this->gpmPeople($filters);
        $identities = [];

        $term = $enabled ? $this->searchTerm($filters) : null;
        if ($term !== null) {
            try {
                $identities = $this->searchIdp($term, $filters);
            } catch (Throwable $e) {
                Log::warning('IdP directory search failed; returning GPM candidates only.', ['error' => $e->getMessage()]);
                $available = false;
            }
        }

        return [
            'data' => $this->merge($group, $people, $identities),
            'idp_enabled' => $enabled,
            'idp_available' => $available,
        ];
    }

    public function asController(ActionRequest $request, Group $group)
    {
        return response()->json($this->handle($group, $request->only(self::FILTERS)));
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('inviteMembers', $request->group);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
        ];
    }

    /** @return array<string, string> only the non-empty filters */
    private function normalizeFilters(array $filters): array
    {
        $clean = [];
        foreach (self::FILTERS as $key) {
            $value = trim((string) ($filters[$key] ?? ''));
            if ($value !== '') {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function gpmPeople(array $filters): array
    {
        if ($filters === []) {
            return [];
        }

        $query = Person::query()
            ->select(['id', 'uuid', 'first_name', 'last_name', 'email', 'user_id', 'institution_id'])
            ->with('institution:id,name');
        foreach ($filters as $column => $value) {
            $query->where($column, 'like', '%'.$value.'%');
        }

        return $query->orderBy('last_name')->orderBy('first_name')->limit(self::GPM_LIMIT)->get()->all();
    }

    /**
     * The most specific typed field long enough to search the directory with.
     */
    private function searchTerm(array $filters): ?string
    {
        $min = (int) config('idp.directory_search.min_query_length', 3);
        foreach (['email', 'last_name', 'first_name'] as $key) {
            if (isset($filters[$key]) && mb_strlen($filters[$key]) >= $min) {
                return $filters[$key];
            }
        }

        return null;
    }

    /** @return array<int, IdpUser> */
    private function searchIdp(string $term, array $filters): array
    {
        $limit = (int) config('idp.directory_search.limit', 10);
        $ttl = (int) config('idp.directory_search.cache_ttl', 30);

        $found = Cache::remember(
            'idp-search:'.md5(mb_strtolower($term).'|'.$limit),
            $ttl,
            fn () => $this->client->searchUsers($term, $limit),
        );

        return array_values(array_filter($found, fn (IdpUser $user) => $this->matchesFilters($user, $filters)));
    }

    private function matchesFilters(IdpUser $user, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            $needle = mb_strtolower($value);
            $matched = match ($key) {
                'first_name' => str_contains(mb_strtolower((string) $user->firstName), $needle),
                'last_name' => str_contains(mb_strtolower((string) $user->lastName), $needle),
                'email' => count(array_filter($user->emails, fn ($e) => str_contains($e, $needle))) > 0,
            };
            if (! $matched) {
                return false;
            }
        }

        return $user->emails !== [];
    }

    /**
     * @param  array<int, Person>  $people
     * @param  array<int, IdpUser>  $identities
     */
    private function merge(Group $group, array $people, array $identities): array
    {
        $identities = $this->withoutLinkedOrRegistered($identities);

        $peopleById = [];
        foreach ($people as $person) {
            $peopleById[$person->id] = $person;
        }
        $idpByPersonId = [];

        $addresses = $identities === [] ? [] : array_merge(...array_map(fn (IdpUser $u) => $u->emails, $identities));
        $unregistered = $addresses === [] ? collect() : Person::query()
            ->select(['id', 'uuid', 'first_name', 'last_name', 'email', 'user_id', 'institution_id'])
            ->with('institution:id,name')
            ->whereNull('user_id')
            ->whereIn(DB::raw('LOWER(email)'), $addresses)
            ->get();

        $unmatched = [];
        foreach ($identities as $identity) {
            $person = $unregistered->first(fn (Person $p) => $identity->hasEmail((string) $p->email));
            if (! $person) {
                $unmatched[] = $identity;
                continue;
            }
            $peopleById[$person->id] ??= $person;
            $idpByPersonId[$person->id] = ['idp_id' => $identity->id, 'idp_email' => mb_strtolower($person->email)];
        }

        $memberPersonIds = GroupMember::query()
            ->where('group_id', $group->id)
            ->whereIn('person_id', array_keys($peopleById))
            ->pluck('person_id')
            ->all();

        $rows = [];
        foreach ($peopleById as $person) {
            $idp = $idpByPersonId[$person->id] ?? null;
            $rows[] = [
                'kind' => 'person',
                'person_id' => $person->id,
                'uuid' => $person->uuid,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'name' => $person->name,
                'email' => $person->email,
                'institution' => $person->institution?->name,
                'has_account' => $person->user_id !== null,
                'has_idp_identity' => $idp !== null,
                'idp_id' => $idp['idp_id'] ?? null,
                'already_member' => in_array($person->id, $memberPersonIds),
            ];
        }
        foreach ($unmatched as $identity) {
            foreach ($identity->emails as $email) {
                $rows[] = [
                    'kind' => 'idp',
                    'person_id' => null,
                    'uuid' => null,
                    'first_name' => $identity->firstName,
                    'last_name' => $identity->lastName,
                    'name' => $identity->name() ?? $email,
                    'email' => $email,
                    'institution' => null,
                    'has_account' => false,
                    'has_idp_identity' => true,
                    'idp_id' => $identity->id,
                    'already_member' => false,
                ];
            }
        }

        return $rows;
    }

    /**
     * Drop identities that already belong to a GPM user, by link or by address.
     *
     * @param  array<int, IdpUser>  $identities
     * @return array<int, IdpUser>
     */
    private function withoutLinkedOrRegistered(array $identities): array
    {
        if ($identities === []) {
            return [];
        }

        $linkedIds = User::query()->whereIn('idp_id', array_map(fn (IdpUser $u) => $u->id, $identities))->pluck('idp_id')->all();
        $addresses = array_merge(...array_map(fn (IdpUser $u) => $u->emails, $identities));
        $ownedAddresses = $addresses === [] ? [] : User::query()
            ->whereIn(DB::raw('LOWER(email)'), $addresses)
            ->pluck('email')
            ->map(fn ($e) => mb_strtolower($e))
            ->all();

        return array_values(array_filter($identities, function (IdpUser $identity) use ($linkedIds, $ownedAddresses) {
            if (in_array($identity->id, $linkedIds, true)) {
                return false;
            }
            foreach ($identity->emails as $email) {
                if (in_array($email, $ownedAddresses, true)) {
                    return false;
                }
            }

            return true;
        }));
    }
}
