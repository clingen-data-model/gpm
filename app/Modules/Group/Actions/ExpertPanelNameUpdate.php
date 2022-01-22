<?php
namespace App\Modules\Group\Actions;

use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\GroupResource;
use App\Modules\Group\Events\ExpertPanelNameUpdated;

class ExpertPanelNameUpdate
{
    use AsController;

    public function handle(Group $group, ?string $longName, ?string $shortName)
    {
        if ($longName != $group->expertPanel->long_base_name || $shortName != $group->expertPanel->short_base_name) {
            $group->expertPanel->update([
                'long_base_name' => $longName,
                'short_base_name' => $shortName
            ]);

            $oldLong = $group->expertPanel->long_base_name;
            $oldShort = $group->expertPanel->short_base_name;

            if ($longName) {
                $group->update([
                    'name' => $longName
                ]);
            }

            event(new ExpertPanelNameUpdated($group, $longName, $shortName, $oldLong, $oldShort));
        }

        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        if (Auth::user()->cannot('update', $group)) {
            throw new AuthorizationException('You do not have permission to update the expert panel\'s name.');
        }

        // Intercept an check for required for approval here?

        $group = $this->handle($group, $request->long_base_name, $request->short_base_name);

        return new GroupResource($group);
    }

    public function rules()
    {
        $expertPanel = request()->group->expertPanel;

        return [
            'long_base_name' => [
                'nullable',
                'max:255',
                Rule::unique('expert_panels', 'long_base_name')
                    ->ignore($expertPanel->id)
                    ->where(function ($query) use ($expertPanel) {
                        $query->whereNotNull('long_base_name')
                            ->whereNull('deleted_at')
                            ->where('expert_panel_type_id', $expertPanel->expert_panel_type_id);
                    })
            ],
            'short_base_name' => [
                'nullable',
                'max:15',
                Rule::unique('expert_panels', 'short_base_name')
                    ->ignore($expertPanel->id)
                    ->where(function ($query) use ($expertPanel) {
                        $query->whereNotNull('short_base_name')
                            ->whereNull('deleted_at')
                            ->where('expert_panel_type_id', $expertPanel->expert_panel_type_id);
                    })
            ],
        ];
    }
}
