<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Storage;

class FundingAwardController extends Controller
{
    public function index(ExpertPanel $expertPanel)
    {
        return FundingAward::query()
            ->where('expert_panel_id', $expertPanel->id)
            ->with(['fundingSource.fundingType', 'contactPis'])
            ->orderByRaw('start_date IS NULL')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->each(function (FundingAward $award) {
                $award->rep_contacts = collect($award->rep_contacts ?? [])
                    ->map(fn ($contact) => [
                        'role'  => $contact['role'] ?? null,
                        'name'  => $contact['name'] ?? null,
                        'email' => $contact['email'] ?? null,
                        'phone' => $contact['phone'] ?? null,
                    ])
                    ->values()
                    ->all();
            });
    }
    
    public function piOptions(Request $request, ExpertPanel $expertPanel)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 25);
        $limit = max(5, min($limit, 50));

        $query = Person::query()
            ->select(['id', 'uuid', 'first_name', 'last_name', 'email', 'phone'])
            ->isActivatedUser();

        if ($q !== '') {
            $like = '%' . $q . '%';
            $starts = $q . '%';

            $query
                ->where(function ($qq) use ($like) {
                    $qq->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhereRaw(
                            "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?",
                            [$like]
                        );
                })
                ->orderByRaw(
                    "CASE
                        WHEN first_name like ? THEN 0
                        WHEN last_name like ? THEN 1
                        WHEN CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ? THEN 2
                        ELSE 3
                    END",
                    [$starts, $starts, $starts]
                );
        } else {
            $query->orderBy('last_name')->orderBy('first_name');
        }

        return $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get()
            ->map(fn (Person $p) => [
                'id'    => $p->id,
                'uuid'  => $p->uuid,
                'name'  => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')),
                'email' => $p->email,
                'phone' => $p->phone,
            ])
            ->values();
    }

    public function download(ExpertPanel $expertPanel, FundingAward $fundingAward)
    {
        abort_unless((int) $fundingAward->expert_panel_id === (int) $expertPanel->id, 404);
        abort_unless($fundingAward->partnership_agreement_file, 404);
        $path = config('app.funding_award_agreements_dir') . '/' . $fundingAward->partnership_agreement_file;
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->download($path, $fundingAward->partnership_agreement_file);
    }
}
