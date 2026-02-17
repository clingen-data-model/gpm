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
            $this->info('It might be OK to run in production, but why chance it?');
            return Command::INVALID;
        }

        // Hopefully we can find an ep that has passed step 1 but not step 2 that has members/genes defined:
        $ep = ExpertPanel::query()
            ->with(['genes', 'group', 'group.members'])
            ->whereNotNull('step_1_approval_date')
            ->whereNull('step_2_approval_date')
            ->whereHas('genes')
            ->whereHas('group.members')
            ->first();
        $this->jinfo(new GroupCheckpointEvent($ep->group));
        $this->jinfo(new StepApproved($ep, 1, $ep->step_1_approval_date));
        $this->jinfo(new MemberAdded($ep->group->members->first()));
        $this->jinfo(new MemberRoleAssigned($ep->group->members->first(), Role::where('name', 'coordinator')->get()));
        $this->jinfo(new GenesAdded($ep->group, $ep->genes));

        $wg = Group::workingGroup()->first();
        $this->jinfo(new GroupCheckpointEvent($wg));
        $this->jinfo(new MemberAdded($wg->members->first()));
        $this->jinfo(new GroupDescriptionUpdated($wg, $wg->description, ''));
        $this->jinfo(new CaptionIconUpdated($wg, $wg->caption, $wg->icon_path));

        $cdwg = Group::cdwg()->first();
        $this->jinfo(new GroupCheckpointEvent($cdwg));
        $this->jinfo(new MemberAdded($cdwg->members->first()));

        return Command::SUCCESS;
    }

    protected function jinfo($event)
    {
        $this->info("\n// --------------------\n");
        $this->info(json_encode($this->factory->makeFromEvent($event), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
