<?php
namespace App\Actions;

use App\Models\Email;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Services\AddressStructureConverter;

class FixEmailAddressFormat
{
    use AsCommand;

    public $commandSignature = 'dev:update-stored-email';
    public $command = null;

    public function __construct(private AddressStructureConverter $addressConverter)
    {
        //code
    }
    

    public function handle()
    {
        Email::all()->each(function ($message) {
            $message->to = $this->addressConverter->convert($message->to);
            $message->from = $this->addressConverter->convert($message->from);
            $message->cc = $this->addressConverter->convert($message->cc);
            $message->bcc = $this->addressConverter->convert($message->bcc);
            $message->save();
        });
    }
}
