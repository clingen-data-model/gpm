<p>
    @if($notifications->count() > 1)
        <h3>There are {{$notifications->count()}} applications awaiting your decision:</h3>
    @else
        <h3>There is an application awaiting your decision:</h3>
    @endif
    <ul>
        @foreach ($notifications as $notification)
        <li>{{$notification->data['group']['display_name']}} - Step {{$notification->data['group']['expert_panel']['current_step']}}</li>
        @endforeach
    </ul>
    <a href="{{url('/')}}">Log in to the GPM to review these applications</a>
</p>
