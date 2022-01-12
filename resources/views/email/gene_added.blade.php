@extends('email.layout')
<h1>Heads up:</h1>
<p>
    {{$group->displayName}} has added the following genes to their approved scope:
</p>
<ul>
    @foreach ($genes as $gene)
        <li>hgnc:{{$gene->hgnc_id}} - {{$gene->gene_symbol}}</li>
    @endforeach
</ul>

@if ($group->isGcep)
    Note that GPM is not yet integrated with the GeneTracker or GCI.
@else
    Note that the GPM is not yet integrated with the VCI.
@endif

<p>Please make sure this gets the appropriate review and attention.</p>

<p>
Thanks!
<br>
GPM Bot.
</p>