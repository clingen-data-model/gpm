<script>
import SearchSelect from '@/components/forms/SearchSelect.vue'
import api from '@/http/api'

export default {
    name: 'GeneSearchSelect',
    components: {
        SearchSelect,
    },
    props: {
        modelValue: {
            required: true,
        }
    },
    emits: [
      'update:modelValue',
    ],
    computed: {
        selectedGene: {
            get () {
                return this.modelValue;
            },
            set (value) {
                this.$emit('update:modelValue', value);
            }
        }
    },
    methods: {
        async search (searchText) {
            return await api.get(`/api/genes/search?query_string=${searchText}`)
                .then(response => {
                    return response.data;
                });
        },
    }
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
    <template #selection-label="{selection}">
      <div v-if="typeof selection == 'object'">
        {{ selection.gene_symbol }}
      </div>
      <div v-else>
        {{ selection }}
      </div>
    </template>
    <template #option="{option}">
      <div v-if="typeof option == 'object'">
        {{ option.gene_symbol }}
      </div>
      <div v-else>
        {{ option }}
      </div>
    </template>
  </SearchSelect>
</template>