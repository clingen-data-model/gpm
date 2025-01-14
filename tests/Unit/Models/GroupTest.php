<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupType;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;
use function Psy\debug;

/**
 * @group group-model
 */
class GroupTest extends TestCase
{
    private Group $wg;
    private Group $vcep;
    private Group $gcep;
    private Group $scvcep;

    public function setup(): void
    {
        parent::setup();
        $this->wg = Group::factory()->wg()->make();
        $this->vcep = Group::factory()->vcep()->make();
        $this->gcep = Group::factory()->gcep()->make();

        $this->scvcep = Group::factory()->scvcep()->make();
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_knows_if_it_is_an_expert_panel():void
    {
        $nonEpGroupType = GroupType::whereNull('curation_product')->firstOrFail();
        $this->assertFalse(Group::factory()->make(['group_type_id' => $nonEpGroupType->id])->isEp);

        $epGroupType = GroupType::whereNotNull('curation_product')->firstOrFail();       
        $this->assertTrue(Group::factory()->make(['group_type_id' => $epGroupType->id])->isEp);
    }
  
    /**
     * @test
     */
    public function it_knows_if_it_is_a_vcep():void
    {
      $this->assertTrue($this->vcep->isVcep);
  
      $this->assertFalse($this->wg->isVcep);
      $this->assertFalse($this->gcep->isVcep);
      $this->assertFalse($this->scvcep->isVcep);
    }
  
    /**
     * @test
     */
    public function it_knows_if_it_curates_variants():void
    {
      $this->assertTrue($this->scvcep->curatesVariants);
      $this->assertTrue($this->vcep->curatesVariants);
  
      $this->assertFalse($this->wg->curatesVariants);
      $this->assertFalse($this->gcep->curatesVariants);  
    }
  
    /**
     * @test
     */
    public function it_knows_if_it_is_a_somatic_cancer():void
    {
      $this->assertTrue($this->scvcep->isSomaticCancer);
  
      $this->assertFalse($this->vcep->isSomaticCancer);
      $this->assertFalse($this->wg->isSomaticCancer);
      $this->assertFalse($this->gcep->isSomaticCancer);  
    }
}