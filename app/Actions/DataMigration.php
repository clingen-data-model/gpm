<?php
namespace App\Actions;

use Carbon\Carbon;
use App\Models\Event;
use Ramsey\Uuid\Uuid;
use App\Models\Document;
use App\Models\LogEntry;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\Coi;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupMember;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;

class DataMigration
{
    use AsAction;

    public function handle()
    {
        Model::unguard();
        DB::transaction(function () {
            $cdwgs = $this->migrateCdwgs()->keyBy('id');
            $this->migrateApplications($cdwgs);
        });
        Model::reguard();
    }
   
    private function migrateApplications($cdwgs)
    {
        DB::table('applications')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($row) use ($cdwgs) {
                $group = $this->createGroupForApplication($row, $cdwgs);
                $approvalDates = json_decode($row->approval_dates, true);

                $data = [
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'group_id' => $group->id,
                    'short_base_name' => $row->short_base_name,
                    'long_base_name' => $row->long_base_name ?? $row->working_name,
                    'expert_panel_type_id' => $row->ep_type_id,
                    'cdwg_id' => $cdwgs->get($row->cdwg_id) ? $cdwgs->get($row->cdwg_id)->id : null,
                    'affiliation_id' => $row->affiliation_id,
                    'date_initiated' => $row->date_initiated,
                    'step_1_approval_date' => isset($approvalDates['step 1']) ? $approvalDates['step 1'] : null,
                    'step_2_approval_date' => isset($approvalDates['step 2']) ? $approvalDates['step 2'] : null,
                    'step_3_approval_date' => isset($approvalDates['step 3']) ? $approvalDates['step 3'] : null,
                    'step_4_approval_date' => isset($approvalDates['step 4']) ? $approvalDates['step 4'] : null,
                    'date_completed' => $row->date_completed,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => $row->deleted_at,
                    'coi_code' => $row->coi_code,
                    'current_step' => $row->current_step
                ];

                if ($row->ep_type_id === 1 && isset($approvalDates['step 1']) && $approvalDates['step 1']) {
                    $approvalDate = Carbon::parse($approvalDates['step 1']);
                    $data['utilize_gt'] = 1;
                    $data['utilize_gci'] = 1;
                    $data['curations_publicly_available'] = 1;
                    $data['pub_policy_reviewed'] = 1;
                    $data['draft_manuscripts'] = 1;
                    $data['recuration_process_review'] = 1;
                    $data['biocurator_training'] = 1;
                    $data['biocurator_mailing_list'] = 1;
                    $data['gci_training_date'] = $approvalDate;
                    $data['gcep_attestation_date'] = $approvalDate;
                    $data['nhgri_attestation_date'] = $approvalDate;
                }
                
                if ($row->ep_type_id === 2 && isset($approvalDates['step 4']) && $approvalDates['step 4']) {
                    $approvalDate = Carbon::parse($approvalDates['step 4']);
                    $data['reanalysis_conflicting'] = 1;
                    $data['reanalysis_review_lp'] = 1;
                    $data['reanalysis_review_lb'] = 1;
                    $data['reanalysis_attestation_date'] = $approvalDates['step 4'];
                    $data['nhgri_attestation_date'] = $approvalDates['step 4'];
                }

                $expertPanel = ExpertPanel::withTrashed()->firstOrCreate(['group_id' => $group->id], $data);

                $this->migrateDocumentsForGroup(
                    $this->queryAppItems('documents', $row->id)->get(),
                    $group,
                    $expertPanel
                );
                $this->migrateNextActionForEp($this->queryAppItems('next_actions', $row->id)->get(), $expertPanel);
                $this->migrateGroupMembers($this->queryAppItems('application_person', $row->id)->get(), $group);
                $activityLogs = DB::table('activity_log')
                                    ->where([
                                        'subject_type' => 'App\Modules\Application\Models\Application',
                                        'subject_id' => $row->id
                                    ])->get();
                $this->migrateActivityLogs($activityLogs, $expertPanel);
                $this->migrateCois($this->queryAppItems('cois', $row->id)->get(), $expertPanel);

                $this->dispatchStepApprovalEvents($expertPanel);
            });
    }

    private function dispatchStepApprovalEvents(ExpertPanel $expertPanel)
    {
        if (!is_null($expertPanel->step_1_approval_date)) {
            event(new StepApproved($expertPanel, 1, Carbon::parse($expertPanel->step_1_approval_date)));
        }
        if (!is_null($expertPanel->step_2_approval_date)) {
            event(new StepApproved($expertPanel, 2, Carbon::parse($expertPanel->step_2_approval_date)));
        }
        if (!is_null($expertPanel->step_3_approval_date)) {
            event(new StepApproved($expertPanel, 3, Carbon::parse($expertPanel->step_3_approval_date)));
        }
        if (!is_null($expertPanel->step_4_approval_date)) {
            event(new StepApproved($expertPanel, 4, Carbon::parse($expertPanel->step_4_approval_date)));
        }
    }
    

    private function migrateActivityLogs($logEntries, $expertPanel)
    {
        foreach ($logEntries as $logEntry) {
            $subjectType = Group::class;
            $subjectId = $expertPanel->group_id;

            // Handle custom logs that were entered via ckeditor
            if (substr($logEntry->description, 0, 1) == '<') {
                $props = json_decode($logEntry->properties);
                DB::table('activity_log')
                    ->where('id', $logEntry->id)
                    ->update([
                        'subject_type' => $subjectType,
                        'subject_id' => $subjectId,
                    ]);
                continue;
            }

            $activityType = $this->parseActivityType($logEntry);
            DB::table('activity_log')
                ->where('id', $logEntry->id)
                ->update([
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'activity_type' => $activityType,
                ]);
        };
    }
   
    public function parseActivityType($row)
    {
        if (!empty($row->activity_type)) {
            return $row->activity_type;
        }

        switch ($row->description) {
            case 'expert-panel-attributes-updated':
                return 'attributes-updated';
            case 'Application initiated':
            case 'Application completed':
            case 'Application deleted':
                return Str::kebab($row->description);
            case 'Step 1 approved':
            case 'Step 2 approved':
            case 'Step 3 approved':
            case 'Step 4 approved':
                return 'step-approved';
            default:
                $patterns = [
                    '/(^Added contact.+)/',
                    '/(^Added next action.+)/',
                    '/(^Attributes updated:.+)/',
                    '/(^COI form completed.+)/',
                    '/(^scope version \d marked final\.?$)/',
                    '/(^Added version \d of .+.$)/',
                    '/(^Application completed\.$)/'
                ];
                $replacements = [
                    'contact-added',
                    'next-action-added',
                    'attributes-updated',
                    'coi-completed',
                    'document-marked-final',
                    'document-added',
                    'document-added',
                    'application-completed'
                ];
                return preg_replace($patterns, $replacements, $row->description);
        }
        if ($row->description == 'Application initiated') {
            return 'appliation-initiated';
        }
    }
    

    private function migrateGroupMembers($rows, $group)
    {
        $rows->each(function ($row) use ($group) {
            GroupMember::withTrashed()
                ->firstOrCreate(
                    [
                        'group_id' => $group->id,
                        'person_id' => $row->person_id
                    ],
                    [
                        'group_id' => $group->id,
                        'person_id' => $row->person_id,
                        'is_contact' => 1
                    ],
                );
        });
    }

    private function migrateCois($rows, $expertPanel)
    {
        $rows->each(function ($row) use ($expertPanel) {
            $uuid = Uuid::uuid4();
            $data = json_decode($row->data);
            if ($data->email === "Legacy Coi") {
                return;
            }
            $person = Person::withTrashed()->firstOrCreate(
                ['email' => $data->email],
                [
                    'uuid' => $uuid->toString(),
                    'email' => $data->email,
                    'first_name' => $data->first_name,
                    'last_name' => $data->last_name
                ]
            );
            $groupMember = GroupMember::withTrashed()->firstOrCreate(
                [
                    'group_id' => $expertPanel->group_id,
                    'person_id' => $person->id
                ],
                [
                    'group_id' => $expertPanel->group_id,
                    'person_id' => $person->id
                ]
            );
            $coi = new Coi;
            $coi->setTable('cois_v2')
                ->create(
                    // ['group_member_id' => $groupMember->id],
                    [
                        'uuid' => Uuid::uuid4(),
                        'expert_panel_id' => $expertPanel->id,
                        'group_member_id' => $groupMember->id,
                        'data' => json_decode($row->data),
                        'completed_at' => $row->created_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]
                );
        });
    }
    
    private function migrateNextActionForEp($nextActions, $expertPanel)
    {
        $nextActions->each(function ($na) use ($expertPanel) {
            $na->expert_panel_id = $expertPanel->id;
            $na->application_step = $na->step;
            $na->assignee_id = $na->assigned_to;
            $na->assignee_name = $na->assigned_to_name;

            unset($na->application_id);
            unset($na->step);
            unset($na->assigned_to);
            unset($na->assigned_to_name);

            $nextAction = new NextAction();
            $nextAction->setTable('next_actions_v2')->withTrashed()->firstOrCreate(['id' => $na->id], (array)$na);
        });
    }

    private function migrateDocumentsForGroup($documents, $group, $expertPanel)
    {
        $documents->each(function ($doc) use ($group, &$expertPanel) {
            $mapped = (array)$doc;
            $mapped['metadata'] = [
                'step' => $doc->step,
                'is_final' => $doc->is_final,
                'version' => $doc->version,
                'date_received' => $doc->date_received
            ];
            $mapped['owner_type'] = get_class($group);
            $mapped['owner_id'] = $group->id;

            unset($mapped['application_id']);
            unset($mapped['is_final']);
            unset($mapped['date_received']);
            unset($mapped['date_reviewed']);
            unset($mapped['step']);
            unset($mapped['version']);

            $document = new Document;
            $document->setTable('documents_v2')->withTrashed()->firstOrCreate(['uuid' => $doc->uuid], $mapped);

            if ($doc->document_type_id == config('documents.types.scope.id') && $doc->version == 1) {
                $expertPanel->step_1_received_date = $doc->date_received;
            }

            if ($doc->document_type_id == config('documents.types.final-app.id') && $doc->version == 1) {
                $expertPanel->step_4_received_date = $doc->date_received;
            }
        });

        $expertPanel->save();
    }

    private function createGroupForApplication($row, $cdwgs)
    {
        $typeId = ($row->ep_type_id == 2)
                    ? config('groups.types.vcep.id')
                    : config('groups.types.gcep.id');


        $group = Group::withTrashed()
                    ->firstOrCreate(
                        ['uuid' => $row->uuid],
                        [
                            'name' => $row->long_base_name ?? $row->working_name,
                            'group_type_id' => $typeId,
                            'group_status_id' => ($row->date_completed)
                                                    ? config('groups.statuses.active.id')
                                                    : config('groups.statuses.applying.id'),
                            'parent_id' => $cdwgs->get($row->cdwg_id) ? $cdwgs->get($row->cdwg_id)->id : null,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                            'deleted_at' => $row->deleted_at
                        ]
                    );
        return $group;
    }


    private function migrateCdwgs()
    {
        $rows = DB::table('cdwgs')->get();

        $cdwgs = $rows->map(function ($row) {
            $group = Group::withTrashed()->firstOrCreate(
                ['uuid' => $row->uuid],
                [
                    'name' => $row->name,
                    'group_type_id' => config('groups.types.cdwg.id'),
                    'group_status_id' => config('groups.statuses.active.id'),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'deleted_at' => null
                ]
            );
            $group->cdwg_id = $row->id;
            return $group;
        })->keyBy('cdwg_id');

        return $cdwgs;
    }

    private function queryAppItems($table, $appId)
    {
        return DB::table($table)
                ->where('application_id', $appId);
    }
}
