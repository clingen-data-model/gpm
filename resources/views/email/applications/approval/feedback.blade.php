@if (count($requiredRevisions))
    <h4>Required Revisions:</h4>
    <ul>
        @foreach ($requiredRevisions as $item)
            <li>{{ $item->content }}</li>
        @endforeach
    </ul>
@endif
@if (count($suggestions))
    <h4>Suggestions:</h4>
    <ul>
        @foreach ($suggestions as $item)
            <li>{{ $item->content }}</li>
        @endforeach
    </ul>
@endif
