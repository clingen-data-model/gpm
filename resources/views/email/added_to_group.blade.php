@extends('email.layout')
{{-- <p>Hi {{$notifiable->first_name}},</p> --}}
<p>Hi there!</p>

<p>We wanted to let you know that you've been added to the {{$group->displayName}} group.</p>

<p>
@if ($group->isEp)
    Please complete your 
    <a href="{{url('/expert-panels/'.$group->expertPanel->fullShortBaseName.'/coi/'.$group->expertPanel->coi_code)}}">
        Conflict of Interest Disclosure
    </a> 
    to become a full member of the expert panel.
@else
    You can see more details in the <a href="{{url('/')}}">ClinGen Group &amp; Personel System</a>.
@endif
</p>

<p>
Thanks,<br />
GPMbot
</p>
