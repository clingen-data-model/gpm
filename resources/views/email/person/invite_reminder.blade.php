@extends('email.layout')
<p>
    Greetings!
</p>

<p>
    @if($invite->inviter)
        We wanted to remind you that you've been invited by the {{$invite->inviter->display_name}} to activate your account in the ClinGen Group & Personnel Management System (GPM).
    @else
        We wanted to remind you that you've been invited to to activate your account in the ClinGen Group & Personnel Management System (GPM).
    @endif

    The ClinGen GPM helps us keep track of gene and variant expert panels, their membership, and the conflict of interest disclosures (COIs) of members.

    To activate your account, please visit 
    <a href="{{url('/invites/'.$invite->code)}}">{{url('/invites/'.$invite->code)}}</a>
    and follow the instructions.
</p>

<p>
    Thanks,<br>
    The ClinGen Team
</p>

