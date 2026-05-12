<?php

namespace Tests\Feature\Integration\Modules\Person\Models;

use Tests\TestCase;
use App\Modules\Person\Models\Invite;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('groups')]
#[Group('invites')]
class InviteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function generates_code_before_saving_if_null()
    {
        $invite = Invite::factory()->make(['code' => null]);

        $this->assertNull($invite->code);

        $invite->save();
        
        $this->assertNotNull($invite->code);
    }
}
