<?php

namespace App\Console\Commands\Dev;

use App\DataExchange\MessageFactories\DxMessageFactory;
use Illuminate\Console\Command;
use App\Models\Role;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Events\MemberRoleAssigned;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GroupCheckpointEvent;
use App\Modules\Group\Events\GroupDescriptionUpdated;
use App\Modules\Group\Events\CaptionIconUpdated;
use App\Modules\Group\Events\SustainedCurationReviewCompleted;
use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;
use App\Modules\ExpertPanel\Events\StepDateApprovedUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class GenerateDxExamples extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dx:generate-examples';
    protected $description = 'Generate example messages for data exchange';
    protected DxMessageFactory $factory;

    protected DxMessageFactory $factory;

    public function __construct(DxMessageFactory $factory)
    {
        parent::__construct();
        $this->factory = $factory;
    }

    public function isHidden(): bool
    {
        return ! app()->isLocal();
    }

    public function handle()
    {
        config(['dx.push-enable' => true]);
        config(['dx.driver' => 'log']);

        if ($this->isHidden()) {
            $this->info('This command is only available in the local environment.');
            return Command::INVALID;
        }

        $this->factory = new DxMessageFactory();

        $vcep = ExpertPanel::query()->typeVcep()->with('group')->first();
        $gcep = ExpertPanel::query()->typeGcep()->with('group')->first();

        if ($vcep) {
            $this->jinfo(new ExpertPanelAttributesUpdated($vcep, [
                'short_base_name' => $vcep->short_base_name,
                'long_base_name'  => $vcep->long_base_name,
                'scope_description' => 'Example scope update (DX sample)',
            ]));

            $this->jinfo(new SustainedCurationReviewCompleted($vcep->group, $vcep->group->tasks()->limit(5)->get()));
        } else {
            $this->warn('No VCEP ExpertPanel found to emit ep_info_updated example.');
        }

        if ($gcep) {
            $this->jinfo(new ExpertPanelAttributesUpdated($gcep, [
                'short_base_name' => $gcep->short_base_name,
                'long_base_name'  => $gcep->long_base_name,
                'membership_description' => 'Example membership update (DX sample)',
            ]));
        } else {
            $this->warn('No GCEP ExpertPanel found to emit ep_info_updated example.');
        }

        $this->emitStepIfPossible($vcep, 1, 'step_1_approval_date'); // vcep_definition_approval
        $this->emitStepIfPossible($vcep, 2, 'step_2_approval_date'); // vcep_draft_specification_approval
        $this->emitStepIfPossible($vcep, 3, 'step_3_approval_date'); // vcep_pilot_approval
        $this->emitStepIfPossible($vcep, 4, 'step_4_approval_date'); // vcep_final_approval
        $this->emitStepIfPossible($gcep, 1, 'step_1_approval_date'); // gcep_final_approval

        $epForDateUpdate = $vcep;
        if ($epForDateUpdate) {
            $this->jinfo(new StepDateApprovedUpdated($epForDateUpdate, 2, now()->toDateString() ));
        } else {
            $this->warn('No ExpertPanel found to emit step_date_approved_updated example.');
        }

        $ep = ExpertPanel::query()
            ->with(['genes', 'group', 'group.members'])
            ->whereNotNull('step_1_approval_date')
            ->whereNull('step_2_approval_date')
            ->whereHas('genes')
            ->whereHas('group.members')
            ->first();
        $this->jinfo(new GroupCheckpointEvent($ep->group));
        // $this->jinfo(new StepApproved($ep, 1, $ep->step_1_approval_date));
        $this->jinfo(new MemberAdded($ep->group->members->first()));
        $this->jinfo(new MemberRoleAssigned($ep->group->members->first(), Role::where('name', 'coordinator')->get()));
        $this->jinfo(new GenesAdded($ep->group, $ep->genes));

        $wg = Group::workingGroup()->with('members')->first();
        $this->jinfo(new GroupCheckpointEvent($wg));
        $this->jinfo(new MemberAdded($wg->members->first()));
        $this->jinfo(new GroupDescriptionUpdated($wg, $wg->description, ''));
        $this->jinfo(new CaptionIconUpdated($wg, $wg->caption, $wg->icon_path));

        $cdwg = Group::cdwg()->with('members')->first();
        $this->jinfo(new GroupCheckpointEvent($cdwg));
        $this->jinfo(new MemberAdded($cdwg->members->first()));

        return Command::SUCCESS;
    }

    protected function emitStepIfPossible(?ExpertPanel $ep, int $step, string $dateColumn): void
    {
        if (! $ep) {
            $this->warn("No EP found (cannot emit step {$step}).");
            return;
        }

        $date = $ep->{$dateColumn} ?? Carbon::now();

        if (! $ep->{$dateColumn}) {
            $this->warn("EP {$ep->id} has no {$dateColumn}; using now() for step {$step} example.");
        }

        $this->jinfo(new StepApproved($ep, $step, $date));
    }

    protected function jinfo($event)
    {
        $this->info("\n// --------------------\n");
        $this->info(json_encode($this->factory->makeFromEvent($event), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
