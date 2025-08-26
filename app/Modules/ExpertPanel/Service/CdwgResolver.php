<?php
namespace App\Modules\ExpertPanel\Service;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupType;

class CdwgResolver
{
    public function resolveAmId(ExpertPanel $ep, int $maxHops = 5): ?int
    {
        $cdwg = $ep->cdwg_id ? Group::find($ep->cdwg_id) : null;

        if (!$cdwg) {
            $cdwgTypeId = (int) GroupType::query()->where('name', 'cdwg')->value('id');
            $g = $ep->group_id ? Group::find($ep->group_id) : null;
            for ($i=0; $i<$maxHops && $g; $i++) {
                if ((int)$g->group_type_id === $cdwgTypeId) { $cdwg = $g; break; }
                $g = $g->parent_id ? Group::find($g->parent_id) : null;
            }
        }

        if (!$cdwg) return 1; // 1 is `None` no AM

        // AM nuance: prefer the CDWG’s parent id if present, else the CDWG id
        return $cdwg->parent_id ? (int)$cdwg->parent_id : (int)$cdwg->id;
    }
}
