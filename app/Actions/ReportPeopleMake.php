<?php

namespace App\Actions;
use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;

class ReportPeopleMake extends ReportMakeAbstract
{
    public $commandSignature = 'report:people';

    public function handle()
    {
        $people = Person::with([
                    'memberships' => function ($q) {
                        $q->isActive();
                    },
                    'memberships.group',
                    'memberships.group.type',
                    'memberships.roles',
                    'country',
                    'credentials',
                    'expertises',
                ])
                ->get();

            $people = $people->map(function ($person) {
                    $data = [];
                    $data['id'] = $person->id;
                    $data['name'] = $person->first_name.' '.$person->last_name;
                    $data['email'] = $person->email;
                    $data['institution'] = $person->institution ? $person->institution->name : null;
                    // $data['address'] = implode(', ', collect([$person->street1, $person->street2, $person->city, $person->state, $person->zip])->filter()->toArray());
                    $data['state'] = $person->state ? $person->state : null;
                    $data['country'] = $person->country ? $person->country->name : null;
                    $data['timezone'] = $person->timezone;
                    $data['phone'] = $person->phone;
                    $data['credentials'] = $person->credentialsAsString;
                    $data['expertises'] = preg_replace('/\n/', '; ', $person->expertisesAsString);
                    $data['active_groups'] = $person->memberships->map(function ($i) {
                                                return $i->group->displayName
                                                    .(
                                                        $i->rolesAsString
                                                        ? ' ('.$i->rolesAsString.')'
                                                        : ''
                                                    );
                                            })->filter()->join("; ");
                    $data['biography'] = $person->biograghy;
                    $data['orcid_id'] = $person->orcid_id;
                    $data['hypothesis_id'] = $person->hypothesis_id;
                    $data['has_registered'] = $person->user_id ? 'yes' : 'no';
                    $data['date created'] = $person->created_at->format('Y-m-d');
                    $data['last_updated'] = $person->updated_at->format('Y-m-d');

                    foreach ($data as $key => $val) {
                        $data[$key] = preg_replace('/\n/', '; ', $val);
                    }

                    return $data;
                });

        return $people->toArray();
    }


    public function asCommand(Command $command)
    {
        dd($this->handle());
    }

}
