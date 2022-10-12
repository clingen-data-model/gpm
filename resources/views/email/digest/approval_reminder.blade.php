<div>
    @if($notifications->count() > 1)
        <h4>There are {{$notifications->count()}} applications awaiting your decision:</h4>
    @else
        <h4>There is an application awaiting your decision:</h4>
    @endif
    <ul>
        @foreach ($notifications as $notification)
        <li>
            <a href="{{url('/applications/'.$notification->data['group']['uuid'])}}">
                {{$notification->data['group']['display_name']}}
            </a>
            - Step {{$notification->data['group']['expert_panel']['current_step']}}
        </li>
        @endforeach
    </ul>
</div>
