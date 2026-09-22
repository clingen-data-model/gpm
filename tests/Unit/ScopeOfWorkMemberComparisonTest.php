<?php

namespace Tests\Unit;

use App\Modules\Group\Services\ScopeOfWorkDisplayComparison;
use PHPUnit\Framework\TestCase;

class ScopeOfWorkMemberComparisonTest extends TestCase
{
    private function member(int $person = 20): array
    {
        return ['id' => $person + 100, 'person_id' => $person, 'person_uuid' => 'person-'.$person,
            'first_name' => 'Alex', 'last_name' => 'Smith', 'email' => 'same@example.org',
            'end_date' => null, 'roles' => [['id' => 102, 'name' => 'chair', 'display_name' => 'Captured Chair']]];
    }

    private function compare(array $before, array $after): array
    {
        return (new ScopeOfWorkDisplayComparison)->handle(
            ['scope_of_work' => ['members' => $before]], ['scope_of_work' => ['members' => $after]],
        );
    }

    public function test_union_uses_person_ids_and_retains_captured_identity_and_roles(): void
    {
        $same = $this->member();
        $result = $this->compare([$same, $this->member(21)], [$same, $this->member(22)]);
        $rows = array_column($result['rows']['members'], null, 'key');
        $this->assertCount(3, $rows);
        $this->assertSame('unchanged', $rows[20]['operation']);
        $this->assertSame('removed', $rows[21]['operation']);
        $this->assertSame('added', $rows[22]['operation']);
        $this->assertSame(121, $rows[21]['before']['id']);
        $this->assertSame('person-21', $rows[21]['before']['person_uuid']);
        $this->assertSame('same@example.org', $rows[21]['before']['email']);
        $this->assertSame(['name' => 'chair', 'label' => 'Captured Chair', 'id' => 102], $rows[21]['before']['roles'][0]);
    }

    public function test_multiple_role_changes_keep_the_member_matched(): void
    {
        $before = $this->member();
        $after = $before;
        $after['roles'] = [['id' => 101, 'name' => 'coordinator', 'display_name' => 'Coordinator']];
        $row = $this->compare([$before], [$after])['rows']['members'][0];
        $this->assertSame('changed', $row['operation']);
        $this->assertSame(['removed', 'added'], array_column($row['roles'], 'operation'));
        $this->assertSame(['chair', 'coordinator'], array_column($row['roles'], 'key'));
        $this->assertSame([], $row['field_changes']);
    }

    public function test_retirement_unretirement_and_missing_dates(): void
    {
        $active = $this->member();
        $retired = array_replace($active, ['end_date' => '2026-01-01 00:00:00']);
        foreach ([[$active, $retired], [$retired, $active]] as [$before, $after]) {
            $row = $this->compare([$before], [$after])['rows']['members'][0];
            $this->assertSame('changed', $row['operation']);
            $this->assertSame('end_date', $row['field_changes'][0]['field']);
            $this->assertSame($before['end_date'] === null, $row['field_changes'][0]['before'] === null);
            $this->assertSame($after['end_date'] === null, $row['field_changes'][0]['after'] === null);
        }
        unset($active['end_date']);
        $row = $this->compare([$active], [$retired])['rows']['members'][0];
        $this->assertSame('unchanged', $row['operation']);
        $this->assertSame(['end_date'], $row['unavailable_fields']);
        $this->assertArrayNotHasKey('end_date', $row['before']);
        $this->assertSame([], $row['field_changes']);
    }

    public function test_application_normalization_preserves_only_captured_fields(): void
    {
        $member = $this->member();
        $application = ['attributes' => [], 'relations' => ['members' => [[
            'attributes' => ['id' => 120, 'person_id' => 20, 'end_date' => null],
            'relations' => ['person' => ['attributes' => ['first_name' => 'Alex', 'last_name' => 'Smith', 'email' => null]],
                'roles' => [['attributes' => $member['roles'][0]]]],
        ]]]];
        $row = (new ScopeOfWorkDisplayComparison)->handle(['scope_of_work' => ['members' => [$member]]], $application)['rows']['members'][0];
        $this->assertArrayHasKey('email', $row['after']);
        $this->assertNull($row['after']['email']);
        $this->assertArrayNotHasKey('person_uuid', $row['after']);
        $this->assertArrayNotHasKey('credentials', $row['after']);
        $this->assertSame(102, $row['after']['roles'][0]['id']);
    }

    public function test_ambiguous_identity_and_missing_roles_are_unavailable(): void
    {
        $member = $this->member();
        $this->assertNull($this->compare([$member, $member], [$member])['rows']['members']);
        $missing = $member;
        unset($missing['roles']);
        $result = $this->compare([$member], [$missing]);
        $this->assertContains('member_roles:20', $result['unavailable_sections']);
        $this->assertFalse($result['rows']['members'][0]['roles_available']);
    }
}
