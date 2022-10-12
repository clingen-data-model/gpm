@extends('email.layout')

@section('content')
    There has been new activity on one or more applications that are currently under review. See the summary below or visit the <a href="{{url('/')}}">GPM</a> for all the details.

    @foreach ($notifications->sortKeys() as $class => $groupNotes)
        <hr>
        @include($class::getDigestTemplate(), ['notifications' => $groupNotes])
    @endforeach
@endsection
