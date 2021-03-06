@extends('email.layout')
<p>
    Greetings!
</p>

<p>
    @if($invite->inviter)
        You have been invited to join the Clinical Genome Resource in the {{$invite->inviter->display_name}}.
    @else
        You have been invited to join the Clinical Genome Resource's Group and Personnel Management System.
    @endif

    To accept this invitation, please visit 
    <a href="{{url('/invites/'.$invite->code)}}">{{url('/invites/'.$invite->code)}}</a>
    and follow the instructions.
</p>

<p>
    Thanks,<br>
    The ClinGen Team
</p>

