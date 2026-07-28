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

    @php
        $acceptUrl = $invite->clerk_invitation_url ?: url('/accept-invitation?code='.$invite->code);
    @endphp

    To accept this invitation, please visit <a href="{{ $acceptUrl }}">here</a> and follow the instructions.
</p>
<p>
    Thanks,<br />
    The ClinGen Team
</p>
<p>    
    Support email: <a href="mailto:gpm_support@clinicalgenome.org">gpm_support@clinicalgenome.org</a>
</p>
