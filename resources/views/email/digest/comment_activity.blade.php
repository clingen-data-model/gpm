<div>
    <h4>New comments have been made on applications pending approval:</h4>
    <ul>
        @php
            $groupNotifications = $notifications->groupBy('data.group.id');
        @endphp
        @foreach ($groupNotifications as $nots)
            @php $group = $nots->first()->data['group'] @endphp
            <li>
                <a href="{{url('/applications/'.$group['uuid'])}}">{{$group['display_name']}}</a>:
                {{$nots->count()}} comments by {{$nots->pluck('data.comment.creator.name')->unique()->join(', ', ' and ')}}
            </li>
        @endforeach
    </ul>
</div>
