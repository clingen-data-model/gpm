<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;

class ReportMultipleEpsMake extends ReportMakeAbstract
{
    
    public $commandSignature = 'reports:multiple-eps';

    public function handle()
    {
        return Person::has('activeExpertPanels', '>=', 2)
                ->select(['first_name', 'last_name', 'id', 'email'])
                ->with([
                    'activeExpertPanels' => function ($q) {
                        $q->select(['groups.id', 'group_type_id', 'name']);
                    },
                    'activeExpertPanels.type',
                    'activeExpertPanels.expertPanel',
                    'activeExpertPanels.expertPanel.type'
                ])
                ->get()
                ->map(function ($p) {
                    $row = [
                        'first_name' => $p->first_name,
                        'last_name' => $p->last_name,
                        'email' => $p->email,
                        'ep count' => $p->activeExpertPanels->count(),
                    ];

                    // foreach ($p->activeExpertPanels as $idx => $ep) {
                    //     $row['expert_panel '.$idx+1] = $ep->displayName;
                    // }

                    return $row;

                })->toArray();
    }
}