@extends('errors::minimal')

@section('title', __('GPM Temporarily Unavailable'))
@section('code', '503')
@section('message', __('GPM is currently undergoing maintenance. Please check back shortly.'))