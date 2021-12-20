<?php

namespace App\Actions;

use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Notifications\DatabaseNotification;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class NotificationMarkRead
{
    use AsController;

    public function handle(DatabaseNotification $notification)
    {
        $notification->markAsRead();
    }

    public function asController(ActionRequest $request, $notificationId)
    {
        $notification = DatabaseNotification::findOrFail($notificationId);

        if (
            $notification->notifiable_type != Person::class
            || Auth::user()->person->id != $notification->notifiable_id
        ) {
            throw new AuthorizationException('You don\'t have permission to do this.');
        }

        $this->handle($notification);
        
        return response('marked read', 200);
    }
}
