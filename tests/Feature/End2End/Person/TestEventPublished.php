<?php

namespace Tests\Feature\End2End\Person;

trait TestEventPublished
{
    protected function assertEventPublished($topic, $eventType, $person): void
    {
        $this->assertDatabaseHas('stream_messages', [
            'topic' => $topic,
            'message->event_type' => $eventType,
            'message->data->person->first_name' => $person->first_name,
            'message->data->person->last_name' => $person->last_name,
            'message->data->person->email' => $person->email,
            'message->data->person->credentials' => $person->credentials,
            'message->data->person->biography' => $person->biography,
            'message->data->person->phone' => $person->phone,
            'message->data->person->profile_photo' => $person->ProfilePhotoUrl,
        ]);
    }

}
