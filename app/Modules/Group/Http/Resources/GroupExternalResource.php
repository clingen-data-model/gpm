<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;

class GroupExternalResource extends JsonResource
{
    use IsPublishableGroupEvent;

    public static $wrap = 'gpm_group';

    function __construct(Group $group)
    {
        parent::__construct($group);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->mapGroupForMessage(true, true);
    }

}
