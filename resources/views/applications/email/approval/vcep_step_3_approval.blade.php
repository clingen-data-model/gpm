@extends('applications.email.approval.layout')

@section('content')
    <p>
        Congratulations on your Step 3 approval. I have updated your VCEP status on your webpage <a href="https://www.clinicalgenome.org/affiliation/{{$expertPanel->affiliation_id}}/">https://www.clinicalgenome.org/affiliation/{{$expertPanel->affiliation_id}}/</a>".
    </p>

    <p>
        <b>VCEP Step 4 Guidance:</b> 

        The next step in the ClinGen VCEP application process is Step 4 - Final VCEP Approval.        
        
        Please refer to Section 2.4 starting on page 14 of the <a href="https://www.clinicalgenome.org/site/assets/files/3263/vcep_protocol_v_8.pdf">ClinGen VCEP Protocol</a> for detailed guidance on this step.
    </p>
@endsection