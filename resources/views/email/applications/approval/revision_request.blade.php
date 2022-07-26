@extends('email.applications.approval.layout')

@section('approval')
    <p>
        Thank you for submitting your application for the <em>{{ $group->displayName }}</em>.
    </p>

    @if (count($requiredRevisions) > 0)
            <h4>The reviewers have requested you revise your application to address the following concerns:</h4>
            <ul>
                @foreach ($requiredRevisions as $item)
                    <li>{{$item->content}}</li>
                @endforeach
            </ul>

    @endif
    @if (count($suggestions) > 0)
            <h4>Please consider the following suggestions</h4>
            <ul>
                @foreach ($suggestions as $item)
                    <li>{{$item->content}}</li>
                @endforeach
            </ul>
    @endif
    @if (count($judgementNotes))
            <h4>The CDWG OC Chairs would also like you to know:</h4>
            <ul>
                @foreach ($judgementNotes as $note)
                    <li>{{$note}}</li>
                @endforeach
            </ul>
    @endif
    <p>
        For further guidance on getting started, please reach out to your ClinGen PI liaison and/or parent CDWG coordinator.
    </p>
@endsection
