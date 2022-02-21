<?php

namespace App\Listeners\Mail;

use App\Models\Email;
use App\Modules\Person\Models\Person;
use App\Services\AddressStructureConverter;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class StoreMailInDatabase
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(private AddressStructureConverter $addressConverter)
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        $email = Email::create([
            'from' => $this->addressConverter->convert($event->message->getFrom()),
            'sender' => $event->message->getSender(),
            'reply_to' => $this->addressConverter->convert($event->message->getReplyTo()),
            'to' => $this->addressConverter->convert($event->message->getTo()),
            'cc' => $this->addressConverter->convert($event->message->getCc()),
            'bcc' => $this->addressConverter->convert($event->message->getBcc()),
            'subject' => $event->message->getSubject(),
            'body' => $event->message->getBody(),
        ]);

        foreach ($event->message->getTo() as $address => $name) {
            $person = Person::findByEmail($address);
            if ($person) {
                $person->emails()->attach($email->id);
            }
        }
    }
}
