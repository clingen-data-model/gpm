<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class MailDraftController extends Controller
{
    protected $stepMessages = [
        1 => 'email.applications.approval.initial_approval',
        2 => 'email.applications.approval.vcep_step_2_approval',
        3 => 'email.applications.approval.vcep_step_3_approval',
        4 => 'email.applications.approval.vcep_step_4_approval',
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

        $ccrecipients = [];
        if (in_array($approvedStepNumber, config('expert-panels.notifications.cc.steps'))) {
            $ccrecipients = collect(config('expert-panels.notifications.cc.recipients'))
                                ->map(fn ($pair) => ['name' => $pair[1], 'email' => $pair[0]]);
        }

        return [
            'to' => $expertPanel->contacts
                        ->pluck('person')
                        ->map(function ($c) {
                            return [
                                'name' => $c->name,
                                'email' => $c->email,
                                'uuid' => $c->uuid
                            ];
                        }),
            'cc' => $ccrecipients,
            'subject' => 'Application step '.$approvedStepNumber.' for your ClinGen expert panel '.$expertPanel->name.' has been approved.',
            'body' => $view->render()
        ];
    }

    public function makeDraft(Request $request, Group $group)
    {
        $templateClass = $request->templateClass;
        if (!class_exists($templateClass)) {
            return abort(404);
        }

        $tpl = new $templateClass($group);

        return [
            'to' => $tpl->getTo(),
            'cc' => $tpl->getCc(),
            'subject' => $tpl->renderSubject(),
            'body' => $tpl->renderBody()
        ];
    }
}
