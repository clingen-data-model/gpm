<?php

namespace App\Actions;

use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\DB;

class ReportPeopleMake extends ReportMakeAbstract
{
    public $commandSignature = 'report:people {--csv=}';

    public function handle(string $csvPath = null)
    {
        DB::connection()->disableQueryLog();
        $fh = null;
        if ($csvPath) {
            if (!str_starts_with($csvPath, '/')) {
                $csvPath = storage_path($csvPath);
            }
            @mkdir(dirname($csvPath), 0775, true);
            $fh = fopen($csvPath, 'w');
            fputcsv($fh, [
                'id',
                'name',
                'email',
                'institution',
                'state',
                'country',
                'timezone',
                'phone',
                'credentials',
                'expertises',
                'active_groups',
                'biography',
                'orcid_id',
                'hypothesis_id',
                'has_registered',
                'date created',
                'last_updated'
            ]);
        }
        
        $rows = [];
        $writeRow = function(array $data) use (&$rows, $fh) {            
            foreach ($data as $k => $v) {
                if (is_string($v)) {
                    $data[$k] = preg_replace('/\r?\n/', '; ', $v);
                }
            }
            if ($fh) {
                fputcsv($fh, [
                    $data['id'],
                    $data['name'],
                    $data['email'],
                    $data['institution'],
                    $data['state'],
                    $data['country'],
                    $data['timezone'],
                    $data['phone'],
                    $data['credentials'],
                    $data['expertises'],
                    $data['active_groups'],
                    $data['biography'],
                    $data['orcid_id'],
                    $data['hypothesis_id'],
                    $data['has_registered'],
                    $data['date created'],
                    $data['last_updated'],
                ]);
            } else {
                $rows[] = $data;
            }
        };

        Person::query()
            ->whereNull('deleted_at')
            ->select([
                'id','first_name','last_name','email','institution_id','state','country_id', 'timezone','phone','biography','orcid_id','hypothesis_id','user_id', 'created_at','updated_at'
            ])
            ->with([
                'memberships' => function ($q) {
                    $q->isActive()
                      ->select(['id','group_id','person_id'])
                      ->with([
                          'group:id,group_type_id,name',
                          'group.type:id,name',
                          'roles:id,name',
                      ]);
                },
                'country:id,name',
                'credentials:id,name',
                'expertises:id,name',
                'institution:id,name',
            ])
            ->orderBy('id')
            ->chunkById(1000, function ($people) use ($writeRow) {

                foreach ($people as $person) {
                    $activeGroups = $person->memberships->map(function ($m) {
                        $label = trim(($m->group->name ?? '').' '.strtoupper(optional($m->group->type)->name));
                        $roles = $m->roles->pluck('name')->filter()->implode(', ');
                        return $roles ? "{$label} ({$roles})" : $label;
                    })->filter()->implode('; ');

                    $data = [];
                    $data['id']             = $person->id;
                    $data['name']           = trim($person->first_name.' '.$person->last_name);
                    $data['email']          = $person->email;
                    $data['institution']    = optional($person->institution)->name;
                    $data['state']          = $person->state ?: null;
                    $data['country']        = optional($person->country)->name;
                    $data['timezone']       = $person->timezone;
                    $data['phone']          = $person->phone;
                    $data['credentials']    = $person->credentialsAsString ?? '';
                    $data['expertises']     = preg_replace('/\r?\n/', '; ', $person->expertisesAsString ?? '');
                    $data['active_groups']  = $activeGroups;
                    $data['biography']      = $person->biography;
                    $data['orcid_id']       = $person->orcid_id;
                    $data['hypothesis_id']  = $person->hypothesis_id;
                    $data['has_registered'] = $person->user_id ? 'yes' : 'no';
                    $data['date created']   = optional($person->created_at)?->format('Y-m-d');
                    $data['last_updated']   = optional($person->updated_at)?->format('Y-m-d');

                    $writeRow($data);
                }

                $people->each->unsetRelations();
                gc_collect_cycles();
            });

        if ($fh) {
            fclose($fh);
            if (method_exists($this, 'info')) {
                $this->info("Wrote {$csvPath}");
            }
            return $csvPath;
        }
        return $rows;
    }

    public function asCommand(Command $command)
    {
        $csv = $command->option('csv');
        return $this->handle($csv);
    }
}
