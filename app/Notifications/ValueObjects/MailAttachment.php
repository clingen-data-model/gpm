<?php

namespace App\Notifications\ValueObjects;

use Illuminate\Http\UploadedFile;

class MailAttachment
{
    protected $originalName;
    protected $path;

    public function __construct($originalName, $path)
    {
        $this->originalName = $originalName;
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getOriginalName()
    {
        return $this->originalName;
    }

    public static function createFromUploadedFile(UploadedFile $uploadedFile)
    {
        return new static($uploadedFile->getClientOriginalName(), $uploadedFile->path());
    }
}
