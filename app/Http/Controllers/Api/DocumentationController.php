<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    public function index()
    {
        return [];
    }

    public function show($slug)
    {
        $filePath =  base_path('user_docs/'.$slug.'.md');
        if (!file_exists($filePath)) {
            return response('Markdown documetnation not found', 404);
        }

        return file_get_contents($filePath);
    }
}
