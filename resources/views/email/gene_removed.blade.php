@extends('email.layout')
<h1>Heads up:</h1>
<p>
    {{$group->displayName}} has removed {{$gene->gene_symbol}} (hgnc:{{$gene->hgnc_id}}) from their approved scope.
</p>

@if ($group->isGcep)
    Note that GPM is not yet integrated with the GeneTracker or GCI.
@else
    Note that the GPM is not yet integrated with the VCI.
@endif

<p>
    Please make sure this gets the appropriate review and attention.
</p>

<p>
Thanks!<br>
GPM Bot.
</p>