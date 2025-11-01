<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PublicIconController
{
    public function show(string $uuid)
    {
        $dir = 'workinggroups';
        foreach (['png','jpg','jpeg','gif'] as $ext) {
            $rel = "{$dir}/{$uuid}.{$ext}";
            if (Storage::disk('public')->exists($rel)) {
                $mtime = Storage::disk('public')->lastModified($rel);
                $resp  = Storage::disk('public')->response($rel);
                return $resp
                    ->setPublic()
                    ->setMaxAge(86400)
                    ->setLastModified((new \DateTime())->setTimestamp($mtime))
                    ->setEtag(md5($rel.$mtime));
            }
        }
        abort(Response::HTTP_NOT_FOUND);
    }
}
