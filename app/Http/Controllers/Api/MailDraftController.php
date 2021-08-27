<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use App\Modules\Application\Models\Application;
use App\Notifications\UserDefinedMailNotification;

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

        // dump($approvedStepNumber);
        // dump(config('applications.notifications.cc.recipients'));
        // dd(config('applications.notifications'));
        $ccrecipients = [];
        if (in_array($approvedStepNumber, config('applications.notifications.cc.steps'))) {
            $ccrecipients = collect(config('applications.notifications.cc.recipients'))
                                ->map(fn ($pair) => ['name' => $pair[1], 'email' => $pair[0]]);
        }

        return [
            'to' => $application->contacts
                        ->map(function ($c) {
                            return [
                                'name' => $c->name,
                                'email' => $c->email,
                                'uuid' => $c->uuid
                            ];
                        }),
            'cc' => $ccrecipients,
            'subject' => 'Application step '.$approvedStepNumber.' for your ClinGen expert panel '.$application->name.' has been approved.',
            'body' => $view->render()
        ];
    }
}
