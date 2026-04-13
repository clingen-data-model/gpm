<?php

namespace App\Modules\Group\Actions;

use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Validation\ValidationException;

class GenesUpdateTier
{
    use AsObject, AsController;

    public function handle(array $ids, ?int $tier)
    {
        if (empty($ids)) {
            throw ValidationException::withMessages([
                'ids' => 'No gene IDs provided.'
            ]);
        }

        Gene::whereIn('id', $ids)
            ->update(['tier' => $tier]);
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $this->handle(
            $request->input('ids', []),
            $request->input('tier')
        );

        return $group->fresh()->load('expertPanel.genes');
    }


    public function authorize(ActionRequest $request, Group $group): bool
    {
        if (Auth::user()->cannot('updateGene', $group)) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:scope_genes,id',
            'tier' => 'nullable|integer|in:1,2,3,4'
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'ids.required' => 'Gene IDs are required.',
            'ids.*.exists' => 'One or more provided gene IDs are invalid.',
            'tier.in' => 'Tier must be one of: 1, 2, 3, 4, or null.'
        ];
    }
}
