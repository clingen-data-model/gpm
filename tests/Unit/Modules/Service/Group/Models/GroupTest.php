<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupType;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group group-model
 */
class GroupTest extends TestCase
{
  use RefreshDatabase;

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
      
      $scvcepType = GroupType::factory()->create(['is_expert_panel' => true, 'curates_variants' => true, 'is_somatic_cancer' => true]);
      $this->scvcep = Group::factory()->make(['group_type_id' => $scvcepType]);
      $this->assertTrue(true);
  }
  

  /**
   * @test
   */
  public function it_knows_if_it_is_an_expert_panel():void
  {
      $nonEpGroupType = GroupType::where('is_expert_panel', 0)->firstOrFail();
      $this->assertFalse(Group::factory()->make(['group_type_id' => $nonEpGroupType->id])->isEp);

      $epGroupType = GroupType::where('is_expert_panel', 1)->firstOrFail();       
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