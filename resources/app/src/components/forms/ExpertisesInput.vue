<script setup>
    import {ref, onMounted } from 'vue'
    import {api} from '@/http'
    import {setupMirror, mirrorProps, mirrorEmits} from '@/composables/setup_working_mirror'
    import SearchSelect from '../forms/SearchSelect.vue';
    import ExpertiseCreateForm from './ExpertiseCreateForm'

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

    const showCreateForm = ref(false);
    const initNewExpertise = () => {
        showCreateForm.value = true;
    }

    const handleNewExpertise = async (cred) => {
        showCreateForm.value = false;
        expertises.value = [...expertises.value, cred]
        workingCopy.value.push(expertises.value.find(c => c.id == cred.id));
    }

    const cancelNewExpertise = () => {
        showCreateForm.value = false;
    }

    const searchText = ref('');
</script>

<template>
    <SearchSelect
        v-model="workingCopy"
        :options="expertises"
        multiple
        showOptionsOnFocus
        showOptionsWhenEmpty
        :searchFunction="searchExpertises"
    >
        <template v-slot:fixedBottomOption>
            <div class="text-sm">
                Don't see your credential? <button class="link" @click="initNewExpertise">Create a new one.</button>
            </div>
        </template>
    </SearchSelect>
    <teleport to='body'>
        <modal-dialog v-model="showCreateForm" title="Add a new credential">
            <ExpertiseCreateForm
                :starterString="searchText"
                @saved="handleNewExpertise"
                @canceled="cancelNewExpertise"
            />
        </modal-dialog>
    </teleport>
</template>
