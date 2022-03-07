@extends('email.layout')
<p>
    Greetings!
</p>

@if($notifiable->membershipsWithPendingCoi->count() > 1)
    <p>
        We wanted to remind you that you have {{$notifiable->membershipsWithPendingCoi->count()}} conflict of interest disclosures to complete for the following groups:
    </p>

    <ul>
        @foreach ($notifiable->membershipsWithPendingCoi as $membership)
            <li>
                <a href="{{$membership->group->coiUrl}}">{{$membership->group->displayName}}</a>
            </li>
        @endforeach
    </ul>
@else
    <p>
        We wanted to remind you that you have a conflict of interest disclosure to complete for <a href="{{$notifiable->membershipsWithPendingCoi->first()->group->coiUrl}}">{{$notifiable->membershipsWithPendingCoi->first()->group->displayName}}</a>
    </p>
@endif

<p>
    Follow the links above to complete your COIs or log in to the <a href="{{url('/')}}">ClinGen GPM</a> and see the list on your dashboard.
</p>

<p>
    Thanks,<br>
    The ClinGen Team
</p>
