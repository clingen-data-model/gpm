@extends('email.layout')

@section('content')
    @foreach ($notifications->sortKeys() as $class => $groupNotes)
        @include($class::getDigestTemplate(), ['notifications' => $groupNotes])
    @endforeach
@endsection
