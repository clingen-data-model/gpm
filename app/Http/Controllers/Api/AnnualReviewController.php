<?php

namespace App\Http\Controllers\Api;

use App\Models\AnnualReview;
use Illuminate\Http\Request;
use App\Models\AnnualReviewWindow;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class AnnualReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = AnnualReview::query()
            ->select(['id', 'expert_panel_id', 'submitter_id', 'completed_at'])
            ->with([
                    'expertPanel' => function ($query) {
                        $query->select(['id', 'expert_panel_type_id', 'long_base_name']);
                    },
                    'submitter' => function ($query) {
                        $query->select(['id', 'person_id']);
                    },
                    'submitter.person' => function ($query) {
                        $query->select('id', 'first_name', 'last_name', 'email');
                    }
                ])->get();

        return $reviews;
    }

    public function show($id)
    {
        $annualReview = AnnualReview::findOrFail($id);
        return $annualReview->load([
            'expertPanel' => function ($query) {
                $query->select(['id', 'expert_panel_type_id', 'long_base_name', 'group_id']);
            },
            'submitter' => function ($query) {
                $query->select(['id', 'person_id']);
            },
            'submitter.person' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email');
            },
            'window',
        ]);
    }

    public function windows(Request $request)
    {
        return AnnualReviewWindow::all();
    }

    public function export(Request $request)
    {
        $annualReviews = AnnualReview::find($request->annual_review_ids);

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=annual_reviews.csv",
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
