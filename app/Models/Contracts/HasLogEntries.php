<?php
namespace App\Models\Contracts;

interface HasLogEntries
{
    public function logEntries();

    public function latestLogEntry();
}
