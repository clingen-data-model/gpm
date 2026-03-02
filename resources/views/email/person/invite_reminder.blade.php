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

    The ClinGen GPM helps us keep track of gene and variant expert panels and working group membership, conflict of interest disclosures (COIs), and the Code of Conduct attestations of members.

    To activate your account, please visit 
    <a href="{{url('/invites/'.$invite->code)}}">{{url('/invites/'.$invite->code)}}</a>
    and follow the instructions.
</p>
<p>
    Thanks,<br />
    The ClinGen Team
</p>
<p>    
    Support email: <a href="mailto:gpm_support@clinicalgenome.org">gpm_support@clinicalgenome.org</a>
</p>

