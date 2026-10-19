@extends('email.layout')

@section('content')
    <p>Dear {{$group->display_name}} Leaders,</p>

    @yield('approval')

    @isset($approvalFeedback)
        @include('email.applications.approval.feedback', $approvalFeedback)
    @endisset

    <p>
        Thanks,
    </p>
    <p>
        {{config('mail.from.name')}}
        <br>
        <a href="mailto:{{config('mail.from.address')}}">{{config('mail.from.address')}}</a>
    </p>
@endsection
