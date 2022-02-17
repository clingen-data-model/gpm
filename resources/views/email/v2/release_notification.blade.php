@extends('email.layout')
<p>
    Greetings!
</p>

<p>
    @if($notifiable->memberships->count() > 0)
        <p>You have been invited to join the ClinGen Group and Personnel Management System (GPM) as a member of the following groups:</p>
        <ul>
            @foreach($notifiable->memberships as $membership)
                <li>{{$membership->group->display_name}}</li>
            @endforeach
        </ul>
    @else
        You have been invited to join the Clinical Genome Resource's Group and Personnel Management System (GPM).
    @endif

    <p>The GPM helps ClinGen keep track of expert panel details such as application status, group membership, member profiles, and conflict of interest disclosures.</p>

    Read the <a href="https://docs.google.com/document/d/1adqRiW8UYTOKpIfBPWDcA-tO7I9Q5UGGYHVpTP9n-6E/edit">Frequently Asked Questions document</a> to learn about activiting common activities in the GPM.

    To activate your account, please visit 
    <a href="{{url('/invites/'.$notifiable->invite->code)}}">{{url('/invites/'.$notifiable->invite->code)}}</a>
    and follow the instructions.
</p>

<p>
    Thanks,<br>
    The ClinGen Team
</p>

