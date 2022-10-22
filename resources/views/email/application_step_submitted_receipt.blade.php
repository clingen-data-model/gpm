@extends('email.layout')

@section('content')
    Dear {{$expertPanel->display_name}},

    <p>Thank you for submitting your application to ClinGen.</p>

    <p>The approval committee will be in touch soon.</p>

    <p>
        Please contact
        <a href="mailto:{{$expertPanel->group->isVcep ? 'cdwg_oversightcommittee@clinicalgenome.org' : 'genecuration@clinicalgenome.org'}}">the {{ $expertPanel->group->isVcep ? 'ClinGen CDWG Oversight Committee' : 'ClinGen Gene Curation Working Group'}}</a>
        if you have any questions.
    </p>
@endSection
