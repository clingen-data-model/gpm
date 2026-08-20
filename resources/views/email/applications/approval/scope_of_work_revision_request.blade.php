@extends('email.applications.approval.layout')

@section('approval')
    <p>
        Thank you for submitting the Scope of Work update for
        <em>{{ $group->displayName }}</em>.
    </p>

    @if ($targetVersion)
        <p>
            Revisions have been requested for Scope of Work version
            <strong>{{ $targetVersion }}</strong>.
        </p>
    @endif

    @if (count($requiredRevisions) > 0)
        <h4>The reviewers have requested revisions to address the following concerns:</h4>

        <ul>
            @foreach ($requiredRevisions as $item)
                <li>{{ $item->content }}</li>
            @endforeach
        </ul>
    @endif

    @if (count($suggestions) > 0)
        <h4>Please also consider the following suggestions:</h4>

        <ul>
            @foreach ($suggestions as $item)
                <li>{{ $item->content }}</li>
            @endforeach
        </ul>
    @endif

    @if (count($judgementNotes) > 0)
        <h4>The CDWG OC Chairs also provided the following notes:</h4>

        <ul>
            @foreach ($judgementNotes as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    @endif

    <p>
        Please review the requested changes in GPM, update the Scope of Work as needed,
        and resubmit the Scope of Work revision when ready.
    </p>

    <p>
        If you have questions, please reach out to your ClinGen grant liaison
        and/or parent CDWG coordinator.
    </p>
@endsection