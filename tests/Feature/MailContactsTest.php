<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Mail\Mailable;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Mail;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Actions\ContactsMail;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Group\Actions\MemberAssignRole;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\Feature\End2End\Groups\Members\SetsUpGroupPersonAndMember;

class MailContactsTest extends TestCase
{
    use FastRefreshDatabase;
    use SetsUpGroupPersonAndMember;

    private $addMember, $assignRole, $groupMember;

    public function setup():void
    {
        parent::setup();
        // $this->seed();
        $this->setupForGroupTest();
        $this->setupEntities();
        $this->addMember = app()->make(MemberAdd::class);
        $this->assignRole = app()->make(MemberAssignRole::class);
    }
    
    /**
     * @test
     */
    public function sends_mailable_to_all_contacts()
    {
        $group = Group::factory()->create();
        $contact1 = GroupMember::factory()->create(['group_id' => $group, 'is_contact' => true]);
        $contact2 = GroupMember::factory()->create(['group_id' => $group, 'is_contact' => true]);
        $contacts = [$contact1, $contact2];

        $mailable = new class extends Mailable {
        };

        Mail::fake();
        app()->make(ContactsMail::class)->handle($group, $mailable);

        Mail::assertSent($mailable::class, function ($mailable) use ($contacts) {
            $contactsArray = collect($contacts)->map(function ($member) {
                $arr = ['name' => $member->person->name, 'address' => $member->person->email];
                return $arr;
            })->toArray();

            return $mailable->to == $contactsArray;
        });
    }
    
}
