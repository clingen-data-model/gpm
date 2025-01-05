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
  /**
   * @test
   */
  public function it_knows_whether_it_is_an_expert_panel():void
  {
    $wg = Group::factory()->wg()->make();
    assertFalse($wg->isEp);

    $cdwg = Group::factory()->cdwg()->make();
    assertFalse($cdwg->isEp);

    $gcep = Group::factory()->gcep()->make();
    assertTrue($gcep->isEp);

    $vcep = Group::factory()->vcep()->make();
    assertTrue($vcep->isEp);

    $scvcep = Group::factory()->gcep()->make();
    assertTrue($scvcep->isEp);
  }
  
}
