<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\Person\Models\Person;

class FundingAwardController extends Controller
{
    public function index(ExpertPanel $expertPanel)
    {
        return FundingAward::query()
            ->where('expert_panel_id', $expertPanel->id)
            ->with(['fundingSource.fundingType', 'contactPis'])
            ->orderByRaw('start_date IS NULL') // nulls last
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }
    
    public function piOptions(Request $request, ExpertPanel $expertPanel)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 25);
        $limit = max(5, min($limit, 50));

        $map = fn (Person $p, bool $inGroup) => [
            'id'       => $p->id,
            'uuid'     => $p->uuid,
            'name'     => trim(($p->first_name ?? '').' '.($p->last_name ?? '')),
            'email'    => $p->email,
            'phone'    => $p->phone,
            'in_group' => $inGroup,
        ];

        $members = $expertPanel->group->activeMemberships()->with('person:id,uuid,first_name,last_name,email,phone')->get();
        $groupPeople = $members->pluck('person')->filter()->unique('id')->values();

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $groupPeople = $groupPeople->filter(function ($p) use ($qLower) {
                $hay = mb_strtolower(trim(($p->first_name ?? '').' '.($p->last_name ?? '').' '.($p->email ?? '')));
                return str_contains($hay, $qLower);
            })->values();
        }

        $groupIds = $groupPeople->pluck('id')->values();
        $groupOut = $groupPeople->sortBy(fn (Person $p) => strtolower(($p->last_name ?? '').' '.($p->first_name ?? '')))->values()->map(fn (Person $p) => $map($p, true));

        $remaining = $limit - $groupOut->count();
        if ($remaining <= 0) {
            return $groupOut->take($limit)->values();
        }

        $others = collect();
        if ($q !== '') {
            $like = '%'.$q.'%';

            $others = Person::query()
                ->select(['id','uuid','first_name','last_name','email','phone'])
                ->isActivatedUser()
                ->whereNotIn('id', $groupIds)
                ->where(function ($qq) use ($like) {
                    $qq->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like);
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit($remaining)
                ->get()
                ->map(fn ($p) => $map($p, false));
        }

        return $groupOut->concat($others)->values();
    }



}
