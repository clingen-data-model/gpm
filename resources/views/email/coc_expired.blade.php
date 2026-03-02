@php
    $name = $person->name ?? trim(($person->first_name ?? '').' '.($person->last_name ?? ''));
    $reason = $reason ?? null;
@endphp

<p>Hi {{ $name ?: 'there' }},</p>

<p>
    We wanted to remind you that you have the ClinGen Code of Conduct attestation to renew.
</p>
<p>    
    <a href="{{ $attestUrl }}">{{ $attestUrl }}</a>
</p>
<p>
    Please follow the links above to log in and complete your Code of Conduct attestation, or log in to the ClinGen GPM and see the list on your dashboard.
</p>

<p>Thanks,<br>The ClinGen Team</p>
