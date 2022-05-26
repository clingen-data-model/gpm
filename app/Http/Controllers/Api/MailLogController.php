<?php

namespace App\Http\Controllers\Api;

use App\Models\Email;
use App\ModelSearchService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MailLogController extends Controller
{
    public function index(Request $request)
    {
        $search = new ModelSearchService(
            modelClass: Email::class,
            whereFunction: function ($query, $where) {
                foreach ($where as $key => $value) {
                    if (!$value) {
                        continue;
                    }
                    if ($key == 'filterString') {
                        $words = explode(' ', $value);
                        foreach ($words as $word) {
                            $query->where('to', 'like', '%'.$word.'%', 'or')
                                ->orWhere('subject', 'like', '%'.$word.'%', 'or')
                                ->orWhere('body', 'like', '%'.$word.'%', 'or');
                        }
                    }
                }

                return $query;
            },
            sortFunction: function ($query, $field, $dir) {
                switch ($field) {
                    case 'to':
                        $query->orderByRaw('`to`->"$[0].address" '.$dir);
                        break;
                    case 'sent_at':
                        $query->orderBy('created_at', $dir);
                        break;
                    default:
                        $query->orderBy($field, $dir);
                        break;
                }
                return $query;
            }
        );
        
        $searchQuery = $search->buildQuery($request->only(['where', 'sort', 'with']));

        \Log::debug(renderQuery($searchQuery));
        
        if ($request->page_size || $request->page) {
            return $searchQuery->paginate($request->get('page_size', 20));
        }
        return $searchQuery->get();
    }
    
}
