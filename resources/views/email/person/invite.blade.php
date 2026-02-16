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
    After accepting the invite you will be asked to fill out required information including a conflict of interest (COI) for each participating group, 
    a Code of Conduct (CoC) attestation, and some demographic information. 
    You can learn more about these modules in our <a href="https://docs.google.com/document/d/1adqRiW8UYTOKpIfBPWDcA-tO7I9Q5UGGYHVpTP9n-6E/" target="_blank">FAQs</a>. 
    If you have any questions, please email us at our support email, listed below.
</p>
<p>
    Thanks,<br />
    The ClinGen Group and Personnel Management (GPM) Team
</p>
<p>    
    Support email: <a href="mailto:gpm_support@clinicalgenome.org">gpm_support@clinicalgenome.org</a>
</p>
