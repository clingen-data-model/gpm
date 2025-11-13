@php
    $name = $person->first_name ?? 'ClinGen member';
    $url  = $url ?? (rtrim(config('app.url'), '/') . '/core-approval-member-attestation');
@endphp

<p>Dear {{ $name }},</p>

<p>
	@if(!empty($vcepNames))
		You have been designated as a Core Approval member for the VCEP{{ count($vcepNames) > 1 ? 's' : '' }} {{ implode(', ', $vcepNames) }}.
	@else
		You have been designated as a Core Approval member for a VCEP.
	@endif
	As part of this designation, you are required to attest to your experience with variant interpretation according to the requirements outlined in the <a href="https://www.clinicalgenome.org/docs/clingen-variant-curation-expert-panel-vcep-protocol/" target="_blank" rel="noopener">ClinGen VCEP protocol</a>.
</p>
<p>
  	Please <a href="{{ $url }}">take your attestation</a> or sign into the ClinGen Group and <a href="https://gpm.clinicalgenome.org/">Personnel Management (GPM)</a> system to take your required attestation.
</p>
<p>
  	The attestation is required only once, even if you are designated on multiple VCEPs.
</p>
<p>
  	If you have questions or need to report an issue, please email <a href="mailto:gpm_support@clinicalgenome.org">gpm_support@clinicalgenome.org</a>.
</p>

<p>— The ClinGen GPM Team</p>