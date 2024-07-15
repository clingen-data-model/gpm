<?php

namespace App\Listeners\Mail;

use App\Models\Email;
use Symfony\Component\Mime\Address;
use App\Modules\Person\Models\Person;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\AddressStructureConverter;
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
            'from' => $this->structureAddressData($event->message->getFrom()),
            'sender' => $this->structureAddressData([$event->message->getSender()]),
            'reply_to' => $this->structureAddressData($event->message->getReplyTo()),
            'to' => $this->structureAddressData($event->message->getTo()),
            'cc' => $this->structureAddressData($event->message->getCc()),
            'bcc' => $this->structureAddressData($event->message->getBcc()),
            'subject' => $event->message->getSubject(),
            'body' => $event->message->getHtmlBody() ?? $event->message->getTextBody(),
        ]);

        foreach ($event->message->getTo() as $address) {
            $person = Person::findByEmail($address->getAddress());
            if ($person) {
                $person->emails()->attach($email->id);
            }
        }
    }

    private function structureAddressData(Array|Address $addresses): array|null
    {
        $addresses = array_filter($addresses);

        if (count($addresses) == 0) {
            return null;
        }

        return array_map(fn(Address $address) => ['name' => $address->getName() ? $address->getName() : null, 'address' => $address->getAddress()], $addresses);
    }
}
