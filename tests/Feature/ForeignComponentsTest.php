<?php

namespace Tests\Feature;

use App\Actions\ForeignComponentsRefresh;
use App\Actions\ReportForeignComponentsMake;
use App\Models\ForeignComponentMember;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Institution;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ForeignComponentsTest extends TestCase
{
    public static function locations(): array
    {
        $cases = [
            'person Canada wins' => [38, 226, 'person'],
            'person US wins' => [226, 38, null],
            'institution Canada fallback' => [null, 38, 'institution'],
            'institution US fallback' => [null, 226, null],
            'no country' => [null, null, null],
            'no institution needed' => [38, null, 'person'],
        ];
        foreach ([4, 89, 161, 174, 226, 227, 234, 44, 98, 103, 114, 178] as $id) {
            $cases['excluded person '.$id] = [$id, 38, null];
            $cases['excluded institution '.$id] = [null, $id, null];
        }
        foreach ([135, 141, 165, 233] as $id) {
            $cases['other island '.$id] = [$id, 226, 'person'];
        }

        return $cases;
    }

    #[DataProvider('locations')]
    public function test_location_priority_and_exclusions(?int $personCountry, ?int $institutionCountry, ?string $source): void
    {
        foreach (array_unique(array_filter([$personCountry, $institutionCountry])) as $id) {
            DB::table('countries')->updateOrInsert(['id' => $id], ['name' => 'Country '.$id]);
        }
        $institution = Institution::factory()->create(['country_id' => $institutionCountry, 'city' => 'Institution city']);
        $person = Person::factory()->create(['country_id' => $personCountry, 'institution_id' => $institution->id, 'city' => 'Person city', 'user_id' => null]);
        GroupMember::factory()->create(['person_id' => $person->id]);

        $this->assertSame($source === null ? 0 : 1, ForeignComponentsRefresh::run());
        $row = ForeignComponentMember::where('person_uuid', $person->uuid)->first();
        if ($source === null) {
            $this->assertNull($row);
            return;
        }
        $this->assertSame($source, $row->location_source);
        $this->assertSame($source === 'person' ? 'Person city' : 'Institution city', $row->city);
        $this->assertSame($personCountry ?? $institutionCountry, $row->country_id);
        $this->assertSame('Country '.$row->country_id, $row->country_name);
        $this->assertSame($institution->name, $row->organization_name);
        $this->assertSame(1, $row->active_membership_count);
        $this->assertNull($row->last_unretired_at);
        $this->assertNull($row->stream_membership_count);
        $this->assertNull($row->last_stream_message_id);
    }

    private function person(): Person
    {
        DB::table('countries')->updateOrInsert(['id' => 38], ['name' => 'Canada']);
        return Person::factory()->create(['country_id' => 38, 'user_id' => null]);
    }

    public function test_membership_lifecycle_and_historical_membership_requirement(): void
    {
        $person = $this->person();
        $this->assertSame(0, ForeignComponentsRefresh::run());
        GroupMember::factory()->create(['person_id' => $person->id, 'start_date' => '2010-01-01', 'end_date' => '2015-01-01']);
        $recent = GroupMember::factory()->create(['person_id' => $person->id, 'start_date' => '2016-01-01', 'end_date' => '2020-01-01']);
        ForeignComponentsRefresh::run();
        $row = ForeignComponentMember::sole();
        $this->assertSame(0, $row->active_membership_count);
        $this->assertSame('2020-01-01', $row->removed_at->toDateString());
        $recent->update(['end_date' => null]);
        GroupMember::factory()->create(['person_id' => $person->id, 'start_date' => '2025-01-01', 'end_date' => null]);
        ForeignComponentsRefresh::run();
        $row->refresh();
        $this->assertSame(2, $row->active_membership_count);
        $this->assertNull($row->removed_at);
        $this->assertSame('2010-01-01', $row->added_at->toDateString());
    }

    public function test_scope_round_trip_preserves_stream_fields_and_does_not_read_stream(): void
    {
        $person = $this->person();
        GroupMember::factory()->create(['person_id' => $person->id]);
        ForeignComponentsRefresh::run();
        $row = ForeignComponentMember::sole();
        $state = ['active_group_uuids' => ['A', 'B', 'C'], 'initial_activation_occurred' => true, 'last_processed_event_date' => '2024-02-03 04:05:06'];
        $row->update(['last_unretired_at' => '2024-02-03 04:05:06', 'stream_membership_count' => 3, 'last_stream_message_id' => 123, 'stream_membership_state' => $state]);
        $createdAt = $row->created_at->toDateTimeString();
        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });
        $person->update(['country_id' => null]);
        ForeignComponentsRefresh::run();
        $this->assertFalse($row->refresh()->is_in_scope);
        $person->update(['country_id' => 38]);
        $this->artisan('foreign-components:refresh')->expectsOutput('Refreshed 1 Foreign Component members.')->assertSuccessful();
        $this->assertTrue($row->refresh()->is_in_scope);
        $this->assertSame(1, ForeignComponentMember::count());
        $this->assertSame($createdAt, $row->created_at->toDateTimeString());
        $this->assertSame('2024-02-03 04:05:06', $row->last_unretired_at->toDateTimeString());
        $this->assertSame(3, $row->stream_membership_count);
        $this->assertSame(123, $row->last_stream_message_id);
        $this->assertSame($state, $row->stream_membership_state);
        $this->assertStringNotContainsString('stream_messages', implode('\n', $queries));
    }

    public function test_refresh_crosses_chunk_boundary_and_is_repeatable(): void
    {
        $person = $this->person();
        $membership = GroupMember::factory()->create(['person_id' => $person->id]);
        $people = [];
        for ($i = 0; $i < 1000; $i++) {
            $people[] = [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'first_name' => 'Batch', 'last_name' => (string) $i,
                'email' => 'foreign-component-'.$i.'@example.test', 'country_id' => 38,
            ];
        }
        DB::table('people')->insert($people);
        $memberships = Person::where('id', '!=', $person->id)->pluck('id')->map(fn ($id) => [
            'person_id' => $id, 'group_id' => $membership->group_id,
            'start_date' => '2020-01-01', 'end_date' => null,
        ])->all();
        DB::table('group_members')->insert($memberships);
        $this->assertSame(1001, ForeignComponentsRefresh::run());
        $this->assertSame(1001, ForeignComponentsRefresh::run());
        $this->assertSame(1001, ForeignComponentMember::where('is_in_scope', true)->count());
    }

    public function test_failed_refresh_rolls_back_scope_and_data_changes(): void
    {
        $person = $this->person();
        GroupMember::factory()->create(['person_id' => $person->id]);
        ForeignComponentsRefresh::run();
        $row = ForeignComponentMember::sole();
        $fail = true;
        DB::listen(function ($query) use (&$fail) {
            if ($fail && str_starts_with($query->sql, 'insert into "foreign_component_members"')) {
                throw new \RuntimeException('Simulated batch failure');
            }
        });
        try {
            ForeignComponentsRefresh::run();
            $this->fail('Expected the batch failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated batch failure', $exception->getMessage());
        } finally {
            $fail = false;
        }
        $this->assertTrue($row->refresh()->is_in_scope);
        $this->assertSame(1, ForeignComponentMember::count());
    }

    public function test_soft_deleted_people_and_memberships_follow_existing_report_scopes(): void
    {
        $person = $this->person();
        $membership = GroupMember::factory()->create(['person_id' => $person->id]);
        ForeignComponentsRefresh::run();
        $membership->delete();
        ForeignComponentsRefresh::run();
        $this->assertFalse(ForeignComponentMember::sole()->is_in_scope);
        $membership->restore();
        ForeignComponentsRefresh::run();
        $this->assertTrue(ForeignComponentMember::sole()->is_in_scope);
        $person->delete();
        ForeignComponentsRefresh::run();
        $this->assertFalse(ForeignComponentMember::sole()->is_in_scope);
    }

    public function test_report_uses_materialized_values_and_sorts_names(): void
    {
        $people = [
            Person::factory()->create(['first_name' => 'Zoe', 'last_name' => 'Able']),
            Person::factory()->create(['first_name' => 'Amy', 'last_name' => 'Able']),
            Person::factory()->create(['first_name' => 'Amy', 'last_name' => 'Zulu']),
        ];
        foreach ($people as $index => $person) {
            ForeignComponentMember::create([
                'person_uuid' => $person->uuid, 'organization_name' => $index === 2 ? 'A organization' : 'B organization',
                'city' => 'Stored city', 'country_name' => 'Stored country', 'added_at' => '2010-01-01',
                'last_unretired_at' => $index === 0 ? '2024-01-01' : null,
                'removed_at' => $index === 0 ? null : '2020-01-01', 'active_membership_count' => $index === 0 ? 2 : 0,
            ]);
        }
        ForeignComponentMember::create(['person_uuid' => fake()->uuid(), 'is_in_scope' => false]);
        $rows = [];
        $report = new ReportForeignComponentsMake();
        $report->streamRows(function ($row) use (&$rows) { $rows[] = $row; });
        $this->assertSame(['Amy Zulu', 'Amy Able', 'Zoe Able'], array_column($rows, 'Name(s) of individual(s) in the group'));
        $this->assertSame($report->csvHeaders(), array_keys($rows[0]));
        $this->assertSame('Stored city', $rows[0]['City']);
        $this->assertSame('Stored country', $rows[0]['Country']);
        $this->assertSame('2010-01-01', $rows[0]['Added date']);
        $this->assertNull($rows[0]['Unretired date']);
        $this->assertSame('2020-01-01', $rows[0]['Removed date']);
        $this->assertSame('Removed', $rows[0]['Status']);
        $this->assertSame('2024-01-01', $rows[2]['Unretired date']);
        $this->assertSame('Active', $rows[2]['Status']);
        $this->assertSame(['N', 'Y', 'Y', '', 'N'], array_slice(array_values($rows[0]), -5));
    }
}
