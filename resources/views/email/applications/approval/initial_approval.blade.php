@extends('email.applications.approval.layout')

@section('content')
    <p>
        Thank you for submitting your application for the <em>{{ $expertPanel->name }}</em>.
        Your {{ $expertPanel->expert_panel_type_id == 1 ? 'GCEP' : 'VCEP step 1 application' }} has been reviewed and
        approved by the ClinGen Clinical Domain Working Group Oversight Committee co-chairs. We will be following up with
        additional information.
    </p>

    <p>
        For further guidance on getting started, please reach out to your ClinGen PI liaison and/or parent CDWG coordinator.
    </p>
@endsection