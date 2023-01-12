@extends('email.layout')
<p>Hi there!</p>

<p>We wanted to let you know that you've been added to the {{$group->displayName}}.</p>

<p>
@if ($group->isEp)
    Please complete your
    <a href="{{url('/coi/'.$group->coi_code)}}">
        Conflict of Interest Disclosure
    </a>
    for this group.
@else
    You can see more details in the <a href="{{url('/')}}">ClinGen Group &amp; Personnel Management System</a>.
@endif
</p>

@if ($notifiable->invite && $notifiable->invite->isPending)
    @if($notifiable->invite->hasInviter)
        <p>You were originally invited to join the GPM by the {{$notifiable->invite->inviter->name}} group.</p>
    @endif

    @if ($group->isEp)
        <p>
            Before you can complete the COI for {{$group->displayName}} you'll need to
            <a href="{{$notifiable->invite->url}}">activate your ClinGen GPM account</a>.
            After activating your account you'll see alerts for your pending COI disclosure(s) on your dashboard.
        </p>
    @else
        <p>
           Before you can access {{$group->displayName}} in the GPM you'll need to
            <a href="{{$notifiable->invite->url}}">activate your ClinGen GPM account</a>.
        </p>
    @endif

    <p>
        For more information about the GPM, account activation, and COI disclosures see the
        <a href="https://docs.google.com/document/d/1adqRiW8UYTOKpIfBPWDcA-tO7I9Q5UGGYHVpTP9n-6E/edit">GPM FAQ</a>.
    </p>

@endif

<p>
@if ($group->hasCoordinator)
    If you have questions about your assignment to {{$group->displayName}}
    please contact
    @if ($group->coordinators->count() > 1)
        one of the coordinators:
        <ul>
        @foreach($group->coordinators as $coordinator)
            <li><a href="mailto:{{$coordinator->person->email}}">{{$coordinator->person->name}}</a></li>
        @endforeach
        </ul>
    @else
    the coordinator, <a href="mailto:{{$group->coordinators->first()->person->email}}">{{$group->coordinators->first()->person->name}}</a>
    @endif
@endif

</p>

<p>
Thanks,<br />
The ClinGen Group and Personnel Management System
</p>
