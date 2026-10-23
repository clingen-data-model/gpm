<script setup>
defineProps({ gene: { type: Object, required: true } })
</script>
<template>
  <p v-if="gene.evidence_summaries_count > 0" class="mb-4 rounded bg-amber-50 p-2 text-sm text-amber-900">
    <strong>{{ gene.gene_symbol }} {{ gene.mondo_id }}</strong>:
    This gene-disease has {{ gene.evidence_summaries_count }} {{ gene.evidence_summaries_count === 1 ? 'Evidence Summary' : 'Evidence Summaries' }}.
    Removing it from the Scope of Work will not delete those summaries. They will remain associated with this gene-disease.
  </p>
</template>
