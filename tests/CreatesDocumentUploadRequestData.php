<?php

namespace Tests;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait CreatesDocumentUploadRequestData
{
    protected function makeDocumentUploadRequestData($DocumentTypeId = 1, $dateReceived = null, $dateReviewed = null, $step = null, $filename = null)
    {
        Storage::fake();
        $filename = $filename ?? 'Test Scope Document.docx';
        $file = UploadedFile::fake()->create(name: $filename, mimeType: 'docx');

        return [
            'uuid' => Uuid::uuid4()->toString(),
            'file' => $file,
            'document_type_id' => $DocumentTypeId,
            'date_received' => $dateReceived,
            'date_reviewed' => $dateReviewed,
            'step' => $step
        ];
    }


}