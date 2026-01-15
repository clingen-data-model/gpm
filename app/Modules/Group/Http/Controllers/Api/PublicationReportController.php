<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Publication;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicationReportController extends Controller
{
    public function index(Request $req)
    {
        $q = Publication::query()
            ->with(['group.expertPanel'])
            ->when($req->start, fn($qq)=>$qq->whereDate('published_at','>=',$req->start))
            ->when($req->end,   fn($qq)=>$qq->whereDate('published_at','<=',$req->end))
            ->when($req->group_id, fn($qq,$gid)=>$qq->where('group_id',$gid))
            ->when($req->type, fn($qq,$t)=>$qq->where('pub_type',$t));

        if ($req->boolean('csv')) {
            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="publications.csv"',
            ];
            return new StreamedResponse(function () use ($q) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Group','EP Type','Pub Type','Title','Journal','PMID','PMCID','DOI','Published','URL']);
                $q->orderBy('published_at','desc')->lazyById(1000)->each(function ($p) use ($out) {
                    fputcsv($out, [
                        optional($p->group)->name,
                        optional($p->group->expertPanel)->type->name ?? null,
                        $p->pub_type,
                        $p->title,
                        $p->journal,
                        $p->pmid,
                        $p->pmcid,
                        $p->doi,
                        optional($p->published_at)?->toDateString(),
                        $p->url,
                    ]);
                });
                fclose($out);
            }, 200, $headers);
        }

        return $q->latest('published_at')->paginate(100);
    }
}
