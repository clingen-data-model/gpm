<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PublicIconController
{
    public function show(string $icon_path)
    {
        $dir  = config('app.workinggroups_icon');
        $rel  = $dir . '/' . $icon_path;
        $disk = Storage::disk('public');

        abort_unless($disk->exists($rel), Response::HTTP_NOT_FOUND);

        $mtime = $disk->lastModified($rel);
        $resp  = $disk->response($rel);
        return $resp->setPublic()->setMaxAge(86400)
            ->setLastModified((new \DateTime())->setTimestamp($mtime))
            ->setEtag(md5($rel.$mtime));
    }
}
