<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Modules\Group\Models\Group;

class DocumentController extends Controller
{
    public function show($uuid)
    {
        $doc = Document::findByUuidOrFail($uuid);
        
        if (Auth::user()->cannot('view', $doc)) {
            throw new AuthorizationException('You don\'t have permission to download this doucment.');
        }

        $path = $doc->storage_path;
        if (!Storage::disk('local')->exists($path)) {
            return response('could not find file at path '.$path, 404);
        }

        return Storage::disk('local')->download($path, $doc->filename);
    }

    public function downloadGroupFinalSpecification(Group $group, Document $document)
    {
        $document = $group->documents()->whereKey($document->id)->where('document_type_id',config('documents.types.final-specs.id'))->firstOrFail();
        $path = $document->storage_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->download($path, $document->filename);
    }
}
