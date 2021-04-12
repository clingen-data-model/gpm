<?php

namespace App\Http\Controllers\Api;

use App\Models\Email;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MailLogController extends Controller
{
    public function index()
    {
        $mail = Email::all();

        return $mail;
    }
    
}
