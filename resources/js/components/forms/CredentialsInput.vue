<script setup>
    import {computed, ref, onMounted } from 'vue'
    import {useStore} from 'vuex'
    import {setupMirror, mirrorProps, mirrorEmits} from '@/composables/setup_working_mirror'
    import SearchSelect from '../forms/SearchSelect.vue';
    import CredentialCreateForm from '../credentials/CredentialCreateForm.vue'

    const props = defineProps({
        ...mirrorProps,
        multiple: {
            type: Boolean,
            default: true
        }
    });
    const emit = defineEmits([...mirrorEmits]);
    const store = useStore();
    const {workingCopy} = setupMirror(props, {emit})

    const credentials = computed(() => {
        return store.getters['credentials/items'];
    });

    const searchText = ref('');
    const searchCredentials = async(keyword, options) => {
        searchText.value = keyword
        return options.filter(o => {
            const pattern = /[.,-]/g
            const normedKeyword = keyword.replace(pattern, '').toLowerCase();
            return o.name.toLowerCase().match(normedKeyword)
                || o.synonyms.some(s => s.name.toLowerCase().match(normedKeyword));
        })
    }

    onMounted(() => {
        store.dispatch('credentials/getItems');
    });

    const showCreateForm = ref(false);
    const initNewCredential = () => {
        showCreateForm.value = true;
    }

    const handleNewCredential = async (cred) => {
        showCreateForm.value = false;
        credentials.value = [...credentials.value, cred]
        workingCopy.value.push(credentials.value.find(c => c.id === cred.id));
    }

    const cancelNewCredential = () => {
        showCreateForm.value = false;
    }

</script>

<template>
    <SearchSelect
        v-model="workingCopy"
        :options="credentials"
        :multiple="multiple"
        showOptionsOnFocus
        showOptionsWhenEmpty
        :searchFunction="searchCredentials"
    >
        <template #fixedBottomOption>
            <div class="text-sm">
                Don't see your credential? <button class="link" @click="initNewCredential">Create a new one.</button>
            </div>
        </template>
    </SearchSelect>
    <teleport to='body'>
        <modal-dialog v-model="showCreateForm" title="Add a new credential">
            <CredentialCreateForm
                :starterString="searchText"
                @saved="handleNewCredential"
                @canceled="cancelNewCredential"
            />
        </modal-dialog>
    </teleport>
</template>
