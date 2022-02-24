@extends('email.layout')

@section('content')
    <p>Dear {{$expertPanel->long_base_name}} Leaders,</p>

    @yield('approval')

    <p>
        Thanks,
    </p>
    <p>
        {{config('mail.from.name')}}
        <br>
        <a href="mailto:{{config('mail.from.address')}}">{{config('mail.from.address')}}</a>
    </p>
@endsection