<script setup>
import { inject } from 'vue'
import { useScopeOfWorkComparison } from '@/composables/scope_of_work_comparison'
import ScopeOfWorkChangeComparison from './ScopeOfWorkChangeComparison.vue'

const props = defineProps({ groupUuid: { type: String, default: null }, submissionId: { type: [Number, String], default: null } })
const sharedState = inject('scopeOfWorkComparisonState', null)
const { comparison, loading, error, retry } = sharedState
  ?? useScopeOfWorkComparison(() => [props.groupUuid, props.submissionId])
</script>

<template>
  <section v-if="groupUuid && submissionId" class="rounded border bg-white p-4 mb-4 text-sm" aria-label="Scope of Work round comparison" :aria-busy="loading">
    <p v-if="loading" role="status">Loading submitted comparison?</p>
    <div v-else-if="error" role="alert">{{ error }} <button type="button" class="btn btn-xs" @click="retry++">Retry</button></div>
    <ScopeOfWorkChangeComparison v-else-if="comparison" :comparison="comparison" />
  </section>
</template>
