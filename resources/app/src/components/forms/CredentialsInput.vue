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

    const credentials = ref([]);
    const getCredentials = async () => {
        credentials.value = await api.get('/api/credentials')
                                .then(rsp => rsp.data);
    }

    const searchCredentials = async(keyword, options) => {
        return options.filter(o => {
            const pattern = /[.,-]/g
            const normedKeyword = keyword.replace(pattern, '').toLowerCase();
            return o.name.toLowerCase().match(normedKeyword)
                || o.synonyms.some(s => s.name.toLowerCase().match(normedKeyword));
        })
    }

    onMounted(() => {
        getCredentials();
    });
</script>

<template>
    <SearchSelect
        v-model="workingCopy"
        :options="credentials"
        multiple
        showOptionsOnFocus
        :searchFunction="searchCredentials"
    >
        <template v-slot:fixedBottomOption>
            <div class="text-sm">
                Don't see your credential? <button class="link">Create a new one.</button>
            </div>
        </template>
    </SearchSelect>
</template>
