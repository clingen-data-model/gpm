<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use App\Modules\Application\Models\Application;

class MailDraftController extends Controller
{
    protected $stepMessages = [
        1 => 'applications.email.approval.initial_approval',
        2 => 'applications.email.approval.vcep_step_2_approval',
        3 => 'applications.email.approval.vcep_step_3_approval',
        4 => 'applications.email.approval.vcep_step_4_approval',
    ];

    public function show($applicationUuid, $approvedStepNumber)
    {
        // dd($applicationUuid);
        $application = Application::findByUuidOrFail($applicationUuid);

        if (!isset($this->stepMessages[$approvedStepNumber])) {
            return abort(404);
        }

        $view = View::make(
            $this->stepMessages[$approvedStepNumber],
            [
                'application' => $application,
                'approvedStep' => $approvedStepNumber,
            ]
        );

        return [
            'to' => $application->contacts
                        ->map(function ($c) {
                            return $c->name . ' <'.$c->email.'>';
                        })
                        ->join(', '),
            'subject' => 'Application step '.$approvedStepNumber.' for your ClinGen expert panel '.$application->name.' has been approved.',
            'body' => $view->render()
        ];
    }

    public function send($applicationUuid, )
    {
        Bus::dispatch(new (
            applicationUuid: $applicationUuid, 
            subject: $
        ))
    }
}
