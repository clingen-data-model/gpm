<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Notifications\UserDefinedMailNotification;

class MailDraftController extends Controller
{
    protected $stepMessages = [
        1 => 'applications.email.approval.initial_approval',
        2 => 'applications.email.approval.vcep_step_2_approval',
        3 => 'applications.email.approval.vcep_step_3_approval',
        4 => 'applications.email.approval.vcep_step_4_approval',
    ];

    public function show($expertPanelUuid, $approvedStepNumber)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);

        if (!isset($this->stepMessages[$approvedStepNumber])) {
            return abort(404);
        }

        $view = View::make(
            $this->stepMessages[$approvedStepNumber],
            [
                'expertPanel' => $expertPanel,
                'approvedStep' => $approvedStepNumber,
            ]
        );

        return [
            'to' => $expertPanel->contacts
                        ->map(function ($c) {
                            return $c->name . ' <'.$c->email.'>';
                        })
                        ->join(', '),
            'subject' => 'Application step '.$approvedStepNumber.' for your ClinGen expert panel '.$expertPanel->name.' has been approved.',
            'body' => $view->render()
        ];
    }
}
