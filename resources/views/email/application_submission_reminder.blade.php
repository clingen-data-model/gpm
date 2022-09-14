@extends('email.layout')

Hi {{$notifiable->first_name}},

@if ($submissions->count() == 1)
<p>There is an expert panel application waiting for your decision:</p>
@else
<p>There are {{$submissions->count()}} expert panel applications waiting for your decision:</p>
@endif
<ul>
    @foreach ($submissions as $submission)
        <li>{{$submission->group->display_name}} step {{$submission->group->expertPanel->current_step}} - sent for approval on {{$submission->sent_to_chairs_at->format('m/d/Y')}}</li>
    @endforeach
</ul>

<p>
    Please <a href="https://clinicalgenome.org">log in to the GPM</a> to review {{$submissions->count() > 1 ?  'these applications' : 'this application'}}.
</p>

<p>
    Thanks,
    <br>
    GPM Bot
</p>
