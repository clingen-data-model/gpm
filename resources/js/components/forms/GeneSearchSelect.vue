<script setup>
import { computed } from 'vue'
import api from '@/http/api'
import SearchSelect from '@/components/forms/SearchSelect.vue'

const props = defineProps({
  modelValue: {
    required: true,
  },
})

const emit = defineEmits([
  'update:modelValue',
])

const selectedGene = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  },
})

async function search(searchText) {
  const response = await api.get(`/api/genes/search?query_string=${searchText}`)
  return response.data
}
</script>
<template>
  <SearchSelect
    v-model="selectedGene"
    :search-function="search"
    style="z-index: 2"
    placeholder="HGNC ID or Gene Symbol"
    key-options-by="id"
  >
    <template #selection-label="{ selection }">
      <div v-if="typeof selection === 'object'">
        {{ selection.gene_symbol }}
      </div>
      <div v-else>
        {{ selection }}
      </div>
    </template>
    <template #option="{ option }">
      <div v-if="typeof option === 'object'">
        {{ option.gene_symbol }}
      </div>
      <div v-else>
        {{ option }}
      </div>
    </template>
  </SearchSelect>
</template>