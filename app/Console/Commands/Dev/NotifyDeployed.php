<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use App\Notifications\Dev\Deployed;

class NotifyDeployed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:deployed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a slack notification that a new pod is being spun up.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user = User::find(1);
        if ($user) {
            $user->notify(new Deployed());
        }
    }
}
