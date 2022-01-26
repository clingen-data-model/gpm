@extends('email.layout')
<p>Hi there!</p>

<p>We wanted to let you know that you've been added to the {{$group->displayName}} group.</p>

<p>
@if ($group->isEp)
    Please complete your 
    <a href="{{url('/coi/'.$group->expertPanel->coi_code)}}">
        Conflict of Interest Disclosure
    </a> 
    for this group.
    {{-- to become a full member of the expert panel. --}}
@else
    You can see more details in the <a href="{{url('/')}}">ClinGen Group &amp; Personnel Management System</a>.
@endif
</p>

<p>
@if ($group->hasCoordinator)
    If you have questions about your assignment to {{$group->displayName}} 
    please contact 
    @if ($group->coordinators->count() > 1)
        one of the coordinators:
        <ul>
        @foreach($group->coordinators as $coordinator) 
            <li><a href="mailto:{{$coordinator->person->email}}">{{$coordinator->person->name}}</a></li>
        @endforeach
        </ul>
    @else
    the coordinator, <a href="mailto:{{$coordinators->first()->person->email}}">{{$coordinators->first()->person->name}}</a>
    @endif 
@endif

</p>

<p>
Thanks,<br />
The ClinGen Group and Personnel Management System
</p>
