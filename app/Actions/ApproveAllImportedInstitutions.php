<?php
namespace App\Actions;

use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Modules\Person\Actions\InstitutionMarkApproved;

class ApproveAllImportedInstitutions
{
    use AsCommand;
    
    public $commandSignature = 'institutions:approve-imported';

    public function __construct(private InstitutionMarkApproved $action)
    {
        //code
    }
    

    public function handle()
    {
        Institution::whereDate('created_at', '=', '2022-02-14')
            ->each(function ($inst) {
                $this->action->handle($inst);
            });
    }
}
