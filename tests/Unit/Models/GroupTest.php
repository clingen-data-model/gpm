<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Modules\Group\Models\Group as GroupModel;
use App\Modules\Group\Models\GroupType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('group-model')]
class GroupTest extends TestCase
{
    private GroupModel $wg;
    private GroupModel $vcep;
    private GroupModel $gcep;
    private GroupModel $scvcep;

    public function setup(): void
    {
        parent::setup();
        $this->wg = GroupModel::factory()->wg()->make();
        $this->vcep = GroupModel::factory()->vcep()->make();
        $this->gcep = GroupModel::factory()->gcep()->make();

        $this->scvcep = GroupModel::factory()->scvcep()->make();
        $this->assertTrue(true);
    }

    #[Test]
    public function it_knows_if_it_is_an_expert_panel():void
    {
        $nonEpGroupType = GroupType::whereNull('curation_product')->firstOrFail();
        $this->assertFalse(GroupModel::factory()->make(['group_type_id' => $nonEpGroupType->id])->isEp);

        $epGroupType = GroupType::whereNotNull('curation_product')->firstOrFail();
        $this->assertTrue(GroupModel::factory()->make(['group_type_id' => $epGroupType->id])->isEp);
    }

    #[Test]
    public function it_knows_if_it_is_a_vcep():void
    {
      $this->assertTrue($this->vcep->isVcep);

      $this->assertFalse($this->wg->isVcep);
      $this->assertFalse($this->gcep->isVcep);
      $this->assertFalse($this->scvcep->isVcep);
    }

    #[Test]
    public function it_knows_if_it_curates_variants():void
    {
      $this->assertTrue($this->scvcep->curatesVariants);
      $this->assertTrue($this->vcep->curatesVariants);

      $this->assertFalse($this->wg->curatesVariants);
      $this->assertFalse($this->gcep->curatesVariants);
    }

    #[Test]
    public function it_knows_if_it_is_a_somatic_cancer():void
    {
      $this->assertTrue($this->scvcep->isSomaticCancer);

      $this->assertFalse($this->vcep->isSomaticCancer);
      $this->assertFalse($this->wg->isSomaticCancer);
      $this->assertFalse($this->gcep->isSomaticCancer);
    }
}
