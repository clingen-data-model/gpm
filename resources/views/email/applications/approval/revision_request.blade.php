@extends('email.applications.approval.layout')

@section('approval')
    <p>
        Thank you for submitting your application for the <em>{{ $group->displayName }}</em>.
    </p>

    <p>
        The reviewers have requested you revise your application to address the following concerns:
    </p>
    <p>
        <strong><em>❗️REVISIONS REQUESTED HERE❗️</em></strong>
    </p>
    <p>
        For further guidance on getting started, please reach out to your ClinGen PI liaison and/or parent CDWG coordinator.
    </p>
@endsection
