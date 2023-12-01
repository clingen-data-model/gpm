<?php

namespace Tests\Feature\Integration\Modules\Person\Models;

use App\Modules\Person\Models\Invite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group groups
 * @group invites
 */
class InviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function generates_code_before_saving_if_null()
    {
        $invite = Invite::factory()->make(['code' => null]);

        $this->assertNull($invite->code);

        $invite->save();

        $this->assertNotNull($invite->code);
    }
}
