@extends('email.layout')

@section('content')
    Dear {{$expertPanel->display_name}},

    <p>Thank you for submitting your application to ClinGen.</p>

    <p>The approval committee will be in touch soon.</p>

    <p>
        Please contact 
        <a href="mailto:cdwg_oversightcommittee@clinicalgenome.org">
            the ClinGen CDWG Oversight Committee
        </a>
        if you have any questions.
    </p>