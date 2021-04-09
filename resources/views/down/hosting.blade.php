@extends('errors.minimal')

@section('title', __('The CCDB is down right now.'))
{{-- @section('code', '503') --}}
@section('message')

<p>There seems to be a problem.</p>

<p>The hosting platform for the CCDB is experiencing problems that will prevent the site from working, so we've taken the site down for the moment.</p>

<p><small>This page will refresh every 30 seconds to check for status changes.</small></p>

<script>
    setTimeout(function () {
        console.log('refresh!')
        window.location = window.location
    }, 30000);
</script>
@endsection
