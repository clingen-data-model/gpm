@extends('email.applications.approval.layout')

@section('approval')
    <p>
        Thank you for submitting your application for the <em>{{ $group->displayName }}</em>.
    </p>

    @include('email.applications.approval.feedback')

    @if (count($judgementNotes))
            <h4>The CDWG OC Chairs would also like you to know:</h4>
            <ul>
                @foreach ($judgementNotes as $note)
                    <li>{{$note}}</li>
                @endforeach
            </ul>
    @endif
    <p>
        For further guidance on getting started, please reach out to your ClinGen grant liaison and/or parent CDWG coordinator.
    </p>
@endsection
