<?php

namespace App\Actions;

use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Person\Actions\PersonInvite;
use League\CommonMark\Inline\Element\Link;

class CreateInviteForGroupMembers
{
    use AsAction;

    public string $commandSignature = 'people:create-invites {--dry-run : Dry run of the command that does not actually create invites.}';
    public string $commandDescription = 'Creates invites for all people with null user_ids';

    public function __construct(private PersonInvite $invitePerson, private LinkUsersAndPeople $linker)
    {
    }

    public function handle($people)
    {
        foreach ($people as $person) {
            if ($person->invite) {
                return;
            }
            $this->invitePerson->handle($person);
        }
    }

    public function asCommand(Command $command)
    {
        Person::all()->each(function ($person) {
            $this->linker->handle($person);
        });

        $people = Person::with(['memberships', 'memberships.group'])
                    ->whereNull('user_id')
                    ->get();

        if ($command->option('dry-run')) {
            $command->info('Will create invites for the '.$people->count().' people:');
            $people->each(function ($person) use ($command) {
                $command->info('--'.$person->name.' ('.$person->email.')');
            });
            return;
        }
        $this->handle($people);

        echo "\n";
    }
}