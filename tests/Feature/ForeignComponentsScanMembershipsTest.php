<?php

namespace Tests\Feature;

use App\Actions\ForeignComponentsScanMemberships;
use App\Models\ForeignComponentMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ForeignComponentsScanMembershipsTest extends TestCase
{
    private function member(array $attributes = []): ForeignComponentMember
    {
        return ForeignComponentMember::create(array_merge(['person_uuid' => (string) Str::uuid()], $attributes));
    }

    private function event(ForeignComponentMember $member, string $type, string $group, int $day, array $overrides = []): int
    {
        return DB::table('stream_messages')->insertGetId(array_merge([
            'topic' => 'gpm-general-events',
            'message' => json_encode([
                'event_type' => $type,
                'date' => sprintf('2024-01-%02d 12:00:00', $day),
                'data' => ['members' => [['uuid' => $member->person_uuid, 'name' => 'Extra member fields']], 'group' => ['uuid' => $group]],
            ]),
        ], $overrides));
    }

    public function test_schema_1_9_9_membership_lifecycle(): void
    {
        $member = $this->member();
        $group = 'ac2e8e71-7997-4008-ad76-7d62da31dfae';
        foreach (['member_added', 'member_retired', 'member_unretired'] as $index => $type) {
            $date = sprintf('2025-08-%02d 15:18:54', $index + 4);
            $id = $this->event($member, $type, $group, 1, [
                'created_at' => '2025-09-01 00:00:00',
                'message' => json_encode([
                    'event_type' => $type, 'schema_version' => '1.9.9', 'date' => $date,
                    'data' => [
                        'group' => ['id' => $group],
                        'expert_panel' => ['id' => 'lower-priority-group'],
                        'members' => [['id' => $member->person_uuid]],
                    ],
                ]),
            ]);
            $stats = ForeignComponentsScanMemberships::run();
            $this->assertSame(0, $stats['errors']);
            $this->assertSame(1, $stats['messages_processed']);
            $member->refresh();
            $this->assertSame($index === 1 ? [] : [$group], $member->stream_membership_state['active_group_uuids']);
            $this->assertSame($date, $member->stream_membership_state['last_processed_event_date']);
            $this->assertSame($id, $member->last_stream_message_id);
        }
        $this->assertSame('2025-08-06 15:18:54', $member->last_unretired_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, ForeignComponentsScanMemberships::run()['messages_processed']);
    }

    public static function lifecycles(): array
    {
        return [
            'first addition' => [['+A'], 1, null],
            'multiple groups' => [['+A', '+B'], 2, null],
            'partially retired' => [['+A', '+B', '-A', '!A'], 2, null],
            'fully retired' => [['+A', '+B', '-A', '-B'], 0, null],
            'unretired' => [['+A', '-A', '!A'], 1, 3],
            'added after retirement' => [['+A', '-A', '+B'], 1, 3],
            'latest reactivation' => [['+A', '-A', '!A', '-A', '+B'], 1, 5],
            'duplicate addition' => [['+A', '+A'], 1, null],
            'duplicate retirement' => [['+A', '-A', '-A'], 0, null],
            'duplicate unretirement' => [['+A', '-A', '!A', '!A'], 1, 3],
            'truncated history' => [['-A', '!A'], 1, null],
        ];
    }

    private function legacyEvent(ForeignComponentMember $member, string $type, string $group, int $day): int
    {
        return $this->event($member, $type, $group, $day, [
            'created_at' => sprintf('2024-01-%02d 12:00:00', $day),
            'message' => json_encode([
                'event_type' => $type, 'schema_version' => null,
                'data' => ['members' => [['id' => $member->person_uuid, 'name' => 'Legacy member']], 'expert_panel' => ['id' => $group]],
            ]),
        ]);
    }

    public function test_legacy_addition_retirement_and_incremental_current_reactivation(): void
    {
        $member = $this->member();
        $this->legacyEvent($member, 'member_added', 'A', 1);
        $this->assertSame(1, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertSame(1, $member->refresh()->stream_membership_count);
        $this->assertNull($member->last_unretired_at);
        $this->legacyEvent($member, 'member_retired', 'A', 2);
        $this->assertSame(1, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertSame(0, $member->refresh()->stream_membership_count);
        $this->event($member, 'member_unretired', 'A', 3, ['created_at' => '2024-02-01 00:00:00']);
        $this->assertSame(1, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertSame('2024-01-03 12:00:00', $member->refresh()->last_unretired_at->format('Y-m-d H:i:s'));
        $before = $member->getRawOriginal();
        $this->assertSame(0, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertSame($before, $member->refresh()->getRawOriginal());
    }

    public function test_abhi_like_mixed_history_rebuild_uses_effective_dates(): void
    {
        $member = $this->member(['last_stream_message_id' => 30789, 'stream_membership_count' => 1]);
        // Deliberately insert out of chronological order to exercise fallback-date sorting.
        $this->event($member, 'member_unretired', 'B', 5);
        $this->legacyEvent($member, 'member_retired', 'B', 4);
        $this->legacyEvent($member, 'member_added', 'A', 1);
        $this->legacyEvent($member, 'member_retired', 'A', 3);
        $maxId = $this->legacyEvent($member, 'member_added', 'B', 2);
        $stats = ForeignComponentsScanMemberships::run(true);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame(5, $stats['messages_processed']);
        $this->assertSame('2024-01-05 12:00:00', $member->refresh()->last_unretired_at->format('Y-m-d H:i:s'));
        $this->assertSame(['B'], $member->stream_membership_state['active_group_uuids']);
        $this->assertSame($maxId, $member->last_stream_message_id);
    }

    #[DataProvider('lifecycles')]
    public function test_membership_transitions(array $events, int $count, ?int $unretiredDay): void
    {
        $member = $this->member(['active_membership_count' => $count]);
        foreach ($events as $index => $event) {
            $id = $this->event($member, ['+' => 'member_added', '-' => 'member_retired', '!' => 'member_unretired'][$event[0]], $event[1], $index + 1);
        }
        $stats = ForeignComponentsScanMemberships::run();
        $member->refresh();
        $this->assertSame(count($events), $stats['messages_processed']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame($count, $member->stream_membership_count);
        $this->assertSame($id, $member->last_stream_message_id);
        $this->assertSame($unretiredDay ? sprintf('2024-01-%02d', $unretiredDay) : null, $member->last_unretired_at?->toDateString());
        $this->assertSame($count, count($member->stream_membership_state['active_group_uuids']));
    }

    public function test_incremental_state_duplicates_and_idempotence(): void
    {
        $member = $this->member();
        $first = $this->event($member, 'member_added', 'A', 1);
        ForeignComponentsScanMemberships::run();
        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query; });
        $this->event($member, 'member_added', 'A', 2);
        $this->event($member, 'member_retired', 'A', 3);
        $last = $this->event($member, 'member_unretired', 'B', 4);
        $stats = ForeignComponentsScanMemberships::run();
        $this->assertSame(3, $stats['messages_processed']);
        $this->assertSame(1, $member->refresh()->stream_membership_count);
        $this->assertSame(['B'], $member->stream_membership_state['active_group_uuids']);
        $this->assertSame($last, $member->last_stream_message_id);
        $this->assertSame('2024-01-04', $member->last_unretired_at->toDateString());
        $selects = array_filter($queries, fn ($q) => str_contains($q->sql, 'select "id", "message"'));
        $this->assertNotEmpty($selects);
        foreach ($selects as $query) {
            $this->assertStringContainsString('"id" > ?', $query->sql);
            $this->assertContains($first, $query->bindings);
        }
        $before = $member->getRawOriginal();
        $this->assertSame(0, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertSame($before, $member->refresh()->getRawOriginal());
    }

    public function test_new_people_get_history_and_unrelated_events_are_ignored(): void
    {
        $existing = $this->member();
        $new = $this->member(['is_in_scope' => false]);
        $this->event($existing, 'member_added', 'A', 1);
        $this->event($new, 'member_added', 'A', 1);
        $this->event($new, 'member_retired', 'A', 2);
        $this->event($new, 'member_unretired', 'A', 3);
        $this->event($existing, 'member_retired', 'A', 4, ['topic' => 'other-topic']);
        $this->event($existing, 'member_removed', 'A', 4);
        $unrelated = new ForeignComponentMember(['person_uuid' => (string) Str::uuid()]);
        $this->event($unrelated, 'member_added', 'A', 1);
        $this->assertSame(1, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertNull($new->refresh()->last_stream_message_id);
        $new->update(['is_in_scope' => true]);
        $this->assertSame(3, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertSame('2024-01-03', $new->refresh()->last_unretired_at->toDateString());
        $this->assertSame(2, ForeignComponentMember::count());
    }

    public function test_chronological_order_and_id_tiebreaker_and_max_checkpoint(): void
    {
        $member = $this->member();
        $this->event($member, 'member_unretired', 'A', 3);
        $this->event($member, 'member_added', 'A', 1);
        $last = $this->event($member, 'member_retired', 'A', 2);
        ForeignComponentsScanMemberships::run();
        $this->assertSame('2024-01-03', $member->refresh()->last_unretired_at->toDateString());
        $this->assertSame($last, $member->last_stream_message_id);
        $this->event($member, 'member_retired', 'A', 3);
        $this->event($member, 'member_added', 'A', 3);
        $this->assertSame(2, ForeignComponentsScanMemberships::run()['messages_processed']);
        $this->assertSame(1, $member->refresh()->stream_membership_count);
    }

    public function test_late_or_malformed_events_preserve_checkpoint_until_rebuild_or_correction(): void
    {
        $member = $this->member();
        $this->event($member, 'member_added', 'A', 2);
        ForeignComponentsScanMemberships::run();
        $before = $member->refresh()->getRawOriginal();
        $this->event($member, 'member_retired', 'A', 1);
        $this->assertSame(1, ForeignComponentsScanMemberships::run()['errors']);
        $this->assertSame($before, $member->refresh()->getRawOriginal());
        $this->assertSame(0, ForeignComponentsScanMemberships::run(true)['errors']);
        $this->assertSame(1, $member->refresh()->stream_membership_count);
        $this->event($member, 'member_retired', 'A', 4);
        $this->event($member, 'member_added', '', 5);
        $before = $member->getRawOriginal();
        $this->assertSame(1, ForeignComponentsScanMemberships::run()['errors']);
        $this->assertSame($before, $member->refresh()->getRawOriginal());
    }

    public function test_no_history_and_mismatch_output_preserve_report_fields(): void
    {
        $member = $this->member(['active_membership_count' => 2, 'added_at' => '2010-01-01']);
        $this->artisan('foreign-components:scan-memberships')
            ->expectsOutputToContain('differs from current GPM count 2')
            ->expectsOutput('No history: 1')->expectsOutput('Mismatches: 1')->assertSuccessful();
        $this->assertSame(2, $member->refresh()->active_membership_count);
        $this->assertSame('2010-01-01', $member->added_at->toDateString());
        $this->assertNull($member->last_stream_message_id);
        $this->assertNull($member->last_unretired_at);
        $this->assertSame(0, $member->stream_membership_count);
    }

    public function test_multiple_members_and_event_batch_boundary(): void
    {
        $member = $this->member();
        $other = $this->member();
        for ($i = 0; $i < 501; $i++) {
            $this->event($member, 'member_added', 'A', 1);
        }
        $this->event($member, 'member_added', 'B', 2, ['message' => json_encode([
            'event_type' => 'member_added', 'date' => '2024-01-02 12:00:00',
            'data' => ['group' => ['uuid' => 'B'], 'members' => [['uuid' => $other->person_uuid], ['uuid' => $member->person_uuid]]],
        ])]);
        $stats = ForeignComponentsScanMemberships::run();
        $this->assertSame(503, $stats['messages_processed']);
        $this->assertSame(2, $member->refresh()->stream_membership_count);
        $this->assertSame(1, $other->refresh()->stream_membership_count);
    }

    public function test_checkpoint_without_state_requires_explicit_rebuild(): void
    {
        $member = $this->member();
        $id = $this->event($member, 'member_added', 'A', 1);
        $member->update(['last_stream_message_id' => $id, 'stream_membership_count' => 1]);
        $this->artisan('foreign-components:scan-memberships')
            ->expectsOutputToContain('Missing or invalid persisted state')
            ->assertFailed();
        $this->assertNull($member->refresh()->stream_membership_state);
        $this->artisan('foreign-components:scan-memberships', ['--rebuild' => true])->assertSuccessful();
        $this->assertSame(['A'], $member->refresh()->stream_membership_state['active_group_uuids']);
    }
}
