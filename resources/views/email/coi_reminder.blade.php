@extends('email.layout')
<p>
    Greetings!
</p>

@if($notifiable->pendingCois->count() > 1)
    <p>
        We wanted to remind you that you have {{$notifiable->pendingCois->count()}} conflict of interest disclosures to complete for the following groups:
    </p>

    <ul>
        @foreach ($notifiable->pendingCois as $coi)
            <li>
                <a href="{{$coi->url}}">{{$coi->group->displayName}}</a>
            </li>
        @endforeach
    </ul>
@else
    <p>
        We wanted to remind you that you have a conflict of interest disclosure to complete for <a href="{{$coi->url}}">{{$coi->group->displayName}}</a>
    </p>
@endif

<p>
    Follow the links above to complete your COIs or log in to the <a href="{{url()}}">ClinGen GPM</a> and see the list on your dashboard.
</p>

<p>
    Thanks,<br>
    The ClinGen Team
</p>
