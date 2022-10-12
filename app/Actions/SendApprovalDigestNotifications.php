<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsCommand;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ApprovalDigestNotification;
use App\Notifications\Contracts\DigestibleNotificationInterface;

class SendApprovalDigestNotifications
{
    use AsCommand;

    public $commandSignature = 'submissions:send-notification-digest';

    public function handle()
    {
        $people = Person::has('unreadNotifications')
                    ->with('unreadNotifications')
                    ->get();

        $people->each(function ($person) {
            $submissionNotifications = $person->unreadNotifications
                                ->filter(function ($notification) {
                                    return implementsInterface(
                                        $notification->type,
                                        DigestibleNotificationInterface::class
                                    );
                                });
            $validNotifications = $submissionNotifications->groupBy('type')
                ->map(function ($group, $class) {
                    $validUnique = $class::getValidUnique($group);
                    return $validUnique;
                })
                ->filter(fn ($group) => $group->count() > 0);

            if ($validNotifications->count() > 0) {
                Notification::send($person, new ApprovalDigestNotification($validNotifications));
            }

            $submissionNotifications->each->markAsRead();
        });
    }
}
