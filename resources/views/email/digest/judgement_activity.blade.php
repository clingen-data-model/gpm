<p>
    <h3>New judgements have been made for applications:</h3>
    <ul>
        @foreach ($notifications as $notification)
        <li>
            {{$notification->data['group']['display_name']}}: {{$notification->data['judgement']['person']['name']}} - {{$notification->data['judgement']['decision']}}
        </li>
        @endforeach
    </ul>
</p>
