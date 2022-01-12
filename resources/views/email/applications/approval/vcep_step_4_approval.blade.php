@extends('email.applications.approval.layout')

@section('content')
    <p>
        Congratulations on being formally approved by the ClinGen Rule Specification Review Committee and the CDWG Oversight Committee as an official ClinGen Variant Curation Expert Panel (VCEP)! The ClinGen website should be updated to reflect your new status and an announcement was circulated in the ClinGen quarterly newsletter. We will also make an announcement on the <a href="https://twitter.com/ClinGenResource">ClinGen Twitter newsfeed</a>.
    </p>

    {{-- <p>
        A copy of your Expert Panel application, including rule specifications, will be posted to the ClinVar website. The SOP for registering and submitting to ClinVar as a 3-star approved VCEP is attached.
    </p> --}}
    <p>
        A copy of your Expert Panel application, including rule specifications, will be posted to the ClinVar website. You can download the SOP for registering and submitting to ClinVar as a 3-star approved VCEP <a href="{{url('/downloads/VCEP%20SOP%20-%20Registering%20and%20Submitting%20to%20ClinVar.pdf')}}">here</a>.
    </p>

    <p>
        We are delighted to welcome you as a ClinGen-approved VCEP. We encourage you to participate in the monthly ClinGen all consortium calls, and we also want to ensure that your VCEP is represented on the twice-yearly Clinical Domain Leadership call.
    </p>
@endsection
