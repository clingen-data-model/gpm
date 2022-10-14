<?php
namespace App\Modules\Group\Actions;

use Illuminate\Support\Str;
use App\Modules\Group\Models\Group;
use Carbon\Carbon;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsController;

class GroupMembersMakeCsv
{
    use AsController;

    public function handle(ActionRequest $request, Group $group)
    {
        $rows = $group->members()
                    ->with('person', 'roles', 'permissions', 'person.institution', 'person.country')
                    ->find(explode(',', $request->member_ids))
                    ->map(function ($member) {
                        return [
                            'first_name' => $member->person->first_name,
                            'last_name' => $member->person->last_name,
                            'email' => $member->person->email,
                            'added' => $member->start_date->format('Y-m-d'),
                            'retired' => $member->end_date ? $member->end_date->format('Y-m-d') : null,
                            'receives_group_notifications' => $member->is_contact ? 'Yes' : 'No',
                            'roles' => $member->roles->pluck('name')->join(",\n"),
                            'expertise' => $member->expertise,
                            'notes' => $member->notes,
                            'training_level_1' => $member->training_level_1 ? 'Yes' : 'No',
                            'training_level_2' => $member->training_level_2 ? 'Yes' : 'No',
                            'institution' => ($member->person->institution) ? $member->person->institution->name : null,
                            'credentials' => $member->person->credentialsAsString,
                            'biography' => $member->person->biography,
                            'phone' => $member->person->phone,
                            'address' => $member->person->addressString,
                            'country' => ($member->person->contry) ? $member->person->contry->name : null,
                            'timezone' => $member->person->timezone,
                        ];
                    });

        if ($rows->count() < 1) {
            return response('No members found', 404);
        }

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($rows->first()));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        $dlFileName = Str::snake(strtolower($group->display_name).'_members_').Carbon::now()->toISOString().'.csv';
        return response()->streamDownload($callback, $dlFileName, ['Content-Type' => 'text/csv']);
    }
}
