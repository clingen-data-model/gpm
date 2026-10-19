<script setup>
import { computed, inject, unref } from 'vue'
import { useStore } from 'vuex'
import GeneCurationStatus from './GeneCurationStatus.vue'
import VcepGeneList from './VcepGeneList.vue'
import ScvcepGeneList from './ScvcepGeneList.vue'

const store = useStore()
const group = computed(() => store.getters['groups/currentItemOrNew'])
const state = inject('scopeOfWorkComparisonState', null)
const comparison = computed(() => unref(state?.comparison))
const loading = computed(() => unref(state?.loading))
const error = computed(() => unref(state?.error))
const unavailable = computed(() => comparison.value && (
  comparison.value.status === 'unavailable'
  || comparison.value.unavailable_sections?.includes('genes')
  || !Array.isArray(comparison.value.rows?.genes)
))
</script>
<template>
  <div class="mb-6">
    <p v-if="loading">Loading submitted gene comparison...</p>
    <p v-else-if="error" role="alert">{{ error }}</p>
    <p v-else-if="unavailable" role="status">The submitted gene comparison is unavailable for this review round.</p>
    <template v-else>
    <p v-if="group.is_gcep && !comparison">
      {{ group.expert_panel.genes.map(g => g.gene_symbol).join(', ') }}
    </p>
    <GeneCurationStatus v-if="group.is_gcep" :genes="group.expert_panel.genes" :group-i-d="group.uuid" :scope-comparison="comparison" :readonly="true" :editing="false" />
    <ScvcepGeneList v-else-if="group.is_scvcep && comparison" :scope-comparison="comparison" :readonly="true" :editing="false" />
    <VcepGeneList v-else-if="group.is_vcep_or_scvcep" :scope-comparison="comparison" :readonly="true" :editing="false" />
    </template>
  </div>
</template>
