<div>
    <h4>New judgements have been made for applications:</h4>
    <ul>
        @foreach ($notifications as $notification)
        <li>
            <a href="{{url('/applications/'.$notification->data['group']['uuid'])}}">{{$notification->data['group']['display_name']}}</a>:
            {{$notification->data['judgement']['person']['name']}}
            - {{Str::title(str_replace('-', ' ', $notification->data['judgement']['decision']))}}
        </li>
        @endforeach
    </ul>
</p>
