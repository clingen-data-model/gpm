<p>
    <h3>New comments have been made on applications pending approval:</h3>
    <ul>
        @php
            $groupNotifications = $notifications->groupBy('data.group.id');
        @endphp
        @foreach ($groupNotifications as $nots)
        <li>
            {{$nots->first()->data['group']['display_name']}}:
            {{$nots->count()}}
            by
            {{$nots->pluck('data.comment.creator.name')->join(', ', ', and ')}}
        </li>
        @endforeach
    </ul>
</p>
