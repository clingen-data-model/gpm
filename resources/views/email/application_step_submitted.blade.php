@extends('email.layout')
<p>Hi {{$notifiable->first_name}},</p>

<p>
    @if($submission->submitter)
        {{$submission->submitter->name}} has submitted the
        {{$submission->type->name}} application for
        {{$submission->group->expertPanel->fullName}}.
    @else
        The {{$submission->type->name}} application for
        {{$submission->group->expertPanel->fullName}} has been submitted.
    @endif
</p>

@if ($submission->notes)
    <strong>Notes from the submitter:</strong>
    <p>
        {{$submission->notes}}
    </p>
@endif

<p>
    <a href="{{url('/applications/'.$submission->group->uuid)}}">Review the application</a>
</p>

<p>
    Cheers,
    <br>
    GPM Bot
</p>