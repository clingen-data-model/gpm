@php
    $name = $person->name ?? trim(($person->first_name ?? '').' '.($person->last_name ?? ''));
    $reason = $reason ?? null;
@endphp

<p>Hi {{ $name ?: 'there' }},</p>

@if ($reason === 'missing')
    <p>
        Our records show you do not currently have a ClinGen <strong>Code of Conduct</strong> attestation on file.
    </p>
@else
    <p>
        Your ClinGen <strong>Code of Conduct</strong> attestation is <strong>expired</strong>
        @if(!empty($expiresAt))
            ({{ optional($expiresAt)->toFormattedDateString() }}).
        @endif
    </p>
@endif

<p>
    Please complete your Code of Conduct attestation here:
    <a href="{{ $attestUrl }}">{{ $attestUrl }}</a>
</p>

<p>
    Full Code of Conduct:
    <a href="{{ $fullLink }}">{{ $fullLink }}</a><br>
    One-page summary:
    <a href="{{ $summaryLink }}">{{ $summaryLink }}</a>
</p>

<p>Thanks,<br>ClinGen</p>
