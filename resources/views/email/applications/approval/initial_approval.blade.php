@extends('email.applications.approval.layout')

@section('approval')
    <p>
        Thank you for submitting your application for the <em>{{ $group->name }}</em>.
        Your {{ $group->group_type_id == 3 ? 'GCEP' : 'VCEP step 1 application' }} has been reviewed and
        approved by the ClinGen Clinical Domain Working Group Oversight Committee co-chairs. We will be following up with
        additional information.
    </p>

    <p>
        For further guidance on getting started, please reach out to your ClinGen PI liaison and/or parent CDWG coordinator.
    </p>
@endsection
