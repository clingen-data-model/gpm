@extends('email.applications.approval.layout')

@section('approval')
    <p>
        Congratulations on your Step 2 approval. I have updated your VCEP status on your webpage <a href="{{$expertPanel->clingenUrl}}">{{$expertPanel->clingenUrl}}</a>.
    </p>

    <p>
        <strong>VCEP Step 3 Guidance:</strong> 
        The next step in the ClinGen VCEP application process is Step 3 - Pilot Rules. Please refer to Section 2.3 starting on page 12 of the <a href="https://www.clinicalgenome.org/docs/clingen-variant-curation-expert-panel-vcep-protocol/">ClinGen VCEP Protocol</a> for detailed guidance on this step.
    </p>

    <p>
        <strong>Variant Curation Interface Access:</strong>
        If you aren't already set up to curate in the Variant Curation Interface (VCI), please contact <a href="mailto:clingen-helpdesk@lists.stanford.edu">clingen-helpdesk@lists.stanford.edu</a> with the list of names and email addresses of everyone who will need an account for the VCI and to be added to your affiliation when you are reading to start curating.
    </p>

    <p>
        <strong>Variant Curation Training (VCI):</strong>
        When your VCEP is ready to start curating in the VCI, contact the Community Curation Committee (<a href="mailto:volunteer@clinicalgenome.org">volunteer@clinicalgenome.org</a>) to see if any Variant Curation Interface (VCI) training sessions are scheduled, if not, they will work with you to set up appropriate training for your curators. Requirements for VCEP curator training are outlined in the <a href="https://www.clinicalgenome.org/docs/clingen-variant-curation-expert-panel-vcep-protocol/">ClinGen VCEP Protocol</a> "Biocurator Proficiency Training" on page 4.
    </p>

    <p>
        Please don't hesitate to reach out with any questions.
    </p>
@endsection