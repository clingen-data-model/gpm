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
use App\Modules\ExpertPanel\Actions\AffiliationUpdate;
use Illuminate\Support\Facades\Log;

class ExpertPanelNameUpdate
{
    use AsController;

    public function __construct(
        private AffiliationUpdate $affiliationUpdate
    ) {
    }

    public function handle(Group $group, ?string $longName, ?string $shortName)
    {
        $expertPanel = $group->expertPanel;
        if ($longName != $expertPanel->long_base_name || $shortName != $expertPanel->short_base_name) {
            $expertPanel->update([
                'long_base_name' => $longName,
                'short_base_name' => $shortName
            ]);

            $oldLong = $expertPanel->long_base_name;
            $oldShort = $expertPanel->short_base_name;

            if ($longName) {
                $group->update([
                    'name' => $longName
                ]);
            }

            event(new ExpertPanelNameUpdated($group, $longName, $shortName, $oldLong, $oldShort));

            if ((int) $expertPanel->affiliation_id > 0) {
                try {
                    $this->affiliationUpdate->handle($expertPanel);
                } catch (\Throwable $e) {
                    Log::warning('AM sync on status change failed', [
                        'group_uuid'        => $group->uuid,
                        'expert_panel_uuid' => (string) $expertPanel->uuid,
                        'old_long_name'     => $oldLong,
                        'old_short_name'    => $oldShort,
                        'new_long_name'     => $longName,
                        'new_short_name'    => $shortName,
                        'message'           => $e->getMessage(),
                        'code'              => $e->getCode(),
                    ]);
                }
            }
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
