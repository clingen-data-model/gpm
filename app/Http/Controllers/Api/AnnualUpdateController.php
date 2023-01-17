<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\AnnualUpdate;
use Illuminate\Http\Request;
use App\Models\AnnualUpdateWindow;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class AnnualUpdateController extends Controller
{
    public function index(Request $request)
    {
        $window = $request->window_id ?? AnnualUpdateWindow::forYear(Carbon::now()->year-1)->first();
        $windowId = $window ? $window->id : null;

        if (!$windowId) {
            $windowId = AnnualUpdateWindow::latest();
        }

        $reviewQuery = AnnualUpdate::query()
            ->select(['id', 'expert_panel_id', 'submitter_id', 'completed_at'])
            ->with([
                'expertPanel' => function ($query) {
                    $query->with('group')->select(['id', 'expert_panel_type_id', 'long_base_name', 'group_id']);
                },
                'submitter' => function ($query) {
                    $query->select(['id', 'person_id']);
                },
                'submitter.person' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email');
                }
            ])
            ->forWindow($windowId);

        $reviews = $reviewQuery->get();

        return $reviews;
    }

    public function show($id)
    {
        $annualUpdate = AnnualUpdate::findOrFail($id);
        $annualUpdate->loadForUse();

        return $annualUpdate;
    }

    public function showLatestForGroup(Group $group)
    {
        if (!$group->isEp) {
            return response('This group does not have annual update.', 404);
        }
        $review = $group->expertPanel->latestAnnualUpdate;
        if (!$review) {
            return response('not found', 404);
        }

        $review->loadForUse();

        return $review;
    }

    public function showForGroup(Group $group, $updateId)
    {
        $update = AnnualUpdate::find($updateId);
        $update->loadForUse();

        return $update;
    }


    public function windows(Request $request)
    {
        return AnnualUpdateWindow::all();
    }

    public function export(Request $request)
    {
        $annualReviews = AnnualUpdate::find($request->annual_update_ids);

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=annual_updates.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $columns = array_keys($annualReviews->first()->toCsvArray());

        $callback = function () use ($annualReviews, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($annualReviews as $annualReview) {
                fputcsv($file, $annualReview->toCsvArray());
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
