<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\AffiliationIdSequence;
use App\Modules\Group\Actions\WorkingGroupAffiliationIdGenerate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GenerateWorkingGroupAffiliationIdTest extends TestCase
{
    use RefreshDatabase;

    private WorkingGroupAffiliationIdGenerate $generator;

    public function setup(): void
    {
        parent::setup();

        $this->setupForGroupTest();

        $this->generator = app(WorkingGroupAffiliationIdGenerate::class);

        AffiliationIdSequence::query()->updateOrCreate(
            ['name' => 'working_group'],
            ['next_value' => 60000]
        );
    }

    #[Test]
    public function it_generates_affiliation_id_for_working_group(): void
    {
        $group = Group::factory()->wg()->create([
            'affiliation_id' => null,
        ]);

        $affiliationId = $this->generator->handle($group);

        $this->assertEquals('60000', $affiliationId);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'affiliation_id' => '60000',
        ]);

        $this->assertDatabaseHas('affiliation_id_sequences', [
            'name' => 'working_group',
            'next_value' => 60001,
        ]);
    }

    #[Test]
    public function it_generates_affiliation_id_for_cdwg(): void
    {
        $group = Group::factory()->cdwg()->create([
            'affiliation_id' => null,
        ]);

        $affiliationId = $this->generator->handle($group);

        $this->assertEquals('60000', $affiliationId);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'affiliation_id' => '60000',
        ]);
    }

    #[Test]
    public function it_generates_affiliation_id_for_sccdwg(): void
    {
        $group = Group::factory()->sccdwg()->create([
            'affiliation_id' => null,
        ]);

        $affiliationId = $this->generator->handle($group);

        $this->assertEquals('60000', $affiliationId);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'affiliation_id' => '60000',
        ]);
    }

    #[Test]
    public function it_does_not_replace_existing_affiliation_id(): void
    {
        $group = Group::factory()->wg()->create([
            'affiliation_id' => '60010',
        ]);

        $affiliationId = $this->generator->handle($group);

        $this->assertEquals('60010', $affiliationId);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'affiliation_id' => '60010',
        ]);

        $this->assertDatabaseHas('affiliation_id_sequences', [
            'name' => 'working_group',
            'next_value' => 60000,
        ]);
    }

    #[Test]
    public function it_skips_existing_affiliation_ids(): void
    {
        Group::factory()->wg()->create([
            'affiliation_id' => '60000',
        ]);

        Group::factory()->cdwg()->create([
            'affiliation_id' => '60001',
        ]);

        $group = Group::factory()->sccdwg()->create([
            'affiliation_id' => null,
        ]);

        $affiliationId = $this->generator->handle($group);

        $this->assertEquals('60002', $affiliationId);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'affiliation_id' => '60002',
        ]);

        $this->assertDatabaseHas('affiliation_id_sequences', [
            'name' => 'working_group',
            'next_value' => 60003,
        ]);
    }

    #[Test]
    public function it_uses_available_gap_before_later_existing_ids(): void
    {
        AffiliationIdSequence::query()
            ->where('name', 'working_group')
            ->update(['next_value' => 60004]);

        foreach (['60001', '60002', '60003', '60007', '60008', '60009', '60010'] as $affiliationId) {
            Group::factory()->wg()->create([
                'affiliation_id' => $affiliationId,
            ]);
        }

        $group = Group::factory()->wg()->create([
            'affiliation_id' => null,
        ]);

        $affiliationId = $this->generator->handle($group);

        $this->assertEquals('60004', $affiliationId);

        $this->assertDatabaseHas('affiliation_id_sequences', [
            'name' => 'working_group',
            'next_value' => 60005,
        ]);
    }

    #[Test]
    public function it_finds_next_available_id_after_gap_is_filled(): void
    {
        AffiliationIdSequence::query()
            ->where('name', 'working_group')
            ->update(['next_value' => 60006]);

        foreach (['60001', '60002', '60003', '60004', '60005', '60006', '60007', '60008', '60009', '60010'] as $affiliationId) {
            Group::factory()->wg()->create([
                'affiliation_id' => $affiliationId,
            ]);
        }

        $group = Group::factory()->wg()->create([
            'affiliation_id' => null,
        ]);

        $affiliationId = $this->generator->handle($group);

        $this->assertEquals('60011', $affiliationId);

        $this->assertDatabaseHas('affiliation_id_sequences', [
            'name' => 'working_group',
            'next_value' => 60012,
        ]);
    }

    #[Test]
    public function it_does_not_generate_affiliation_id_for_ep_group(): void
    {
        $group = Group::factory()->gcep()->create([
            'affiliation_id' => null,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->generator->handle($group);
    }
}