<script>
import api from '@/http/api'
import SearchSelect from '@/components/forms/SearchSelect.vue'

export default {
    name: 'DiseaseSearchSelect',
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
    data() {
        return {
            
        }
    },
    computed: {
        selectedDisease: {
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
            return await api.get(`/api/diseases/search?query_string=${searchText}`)
                .then(response => {
                    return response.data;
                });
        }
    }
}
</script>
<template>
  <SearchSelect 
    v-model="selectedDisease" 
    :search-function="search"
    style="z-index: 2"
    placeholder="MonDO ID or name"
  >
    <template #selection-label="{selection}">
      <div v-if="typeof selection == 'object'">
        {{ selection.mondo_id }} - {{ selection.name }}
      </div>
      <div v-else>
        {{ selection }}
      </div>
    </template>
    <template #option="{option}">
      <div v-if="typeof option == 'object'">
        {{ option.mondo_id }} - {{ option.name }}
      </div>
      <div v-else>
        {{ option }}
      </div>
    </template>
  </SearchSelect>
</template>