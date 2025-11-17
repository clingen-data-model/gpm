@php
    $name = $person->first_name . " " . $person->last_name ?? 'ClinGen member';
    $url  = $url ?? (rtrim(config('app.url'), '/') . '/core-approval-member-attestation');
	$panelList = collect($vcepNames ?? [])->filter()->map(fn ($n) => trim($n).' VCEP')->join(', ', ', and ');
@endphp

<p>Dear {{ $name }},</p>

<p>
	@if($panelList)
		You have been designated as a Core Approval member for {{ $panelList }}.
	@else
		You have been designated as a Core Approval member for a VCEP.
	@endif
	As a part of this designation, you are required to attest to your experience with variant classification according to the requirement outlined in the <a href="https://www.clinicalgenome.org/docs/clingen-variant-curation-expert-panel-vcep-protocol/" target="_blank" rel="noopener">ClinGen VCEP protocol</a>.
</p>
<p>
  	Please follow the <a href="{{ $url }}">link</a> or sign into the ClinGen Group and Personnel Management (<a href="https://gpm.clinicalgenome.org/">GPM</a>) system to take your required attestation.
</p>
<p>
  	The attestation is required only once, even if you are designated as a Core Approval member on multiple VCEPs.
</p>
<p>
  	If you have questions or need to report an issue, please email <a href="mailto:gpm_support@clinicalgenome.org">gpm_support@clinicalgenome.org</a>.
</p>

<p>— The ClinGen GPM Team</p>