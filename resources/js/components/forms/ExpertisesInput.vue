<script setup>
    import {ref, onMounted } from 'vue'
    import {api} from '@/http'
    import {setupMirror, mirrorProps, mirrorEmits} from '@/composables/setup_working_mirror'
    import SearchSelect from '../forms/SearchSelect.vue';

    const props = defineProps({
        ...mirrorProps
    });

    const emit = defineEmits([...mirrorEmits]);

    const {workingCopy} = setupMirror(props, {emit})

    const expertises = ref([]);
    const getExpertises = async () => {
        expertises.value = await api.get('/api/expertises')
                                .then(rsp => rsp.data);
    }

    const searchText = ref('');
    const searchExpertises = async(keyword, options) => {
        searchText.value = keyword
        return options.filter(o => {
            const pattern = /[.,-]/g
            const normedKeyword = keyword.replace(pattern, '').toLowerCase();
            return o.name.toLowerCase().match(normedKeyword)
                || o.synonyms.some(s => s.name.toLowerCase().match(normedKeyword));
        })
    }

    onMounted(() => {
        getExpertises();
    });

</script>

<template>
  <SearchSelect
    v-model="workingCopy"
    :options="expertises"
    multiple
    show-options-on-focus
    show-options-when-empty
    :search-function="searchExpertises"
  />
</template>
