<script setup>
    import {computed, ref, onMounted } from 'vue'
    import {setupMirror, mirrorProps, mirrorEmits} from '@/composables/setup_working_mirror'
    import { useCredentialsStore } from '@/stores/credentials';
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
    const {workingCopy} = setupMirror(props, {emit})

    const credentials = computed(() => {
        return useCredentialsStore().items;
    });

    const searchText = ref('');
    const normalize = (s) => (s ?? '').replace(/[.,-]/g, '').toLowerCase().trim();
    const searchCredentials = async (keyword, options) => {
      searchText.value = keyword;

      const k = normalize(keyword);

      const matches = options.filter(o => {
        const name = normalize(o.name);
        const syns = (o.synonyms ?? []).map(s => normalize(s.name));

        return name.includes(k) || syns.some(s => s.includes(k));
      });

      const score = (o) => {
        const name = normalize(o.name);
        const syns = (o.synonyms ?? []).map(s => normalize(s.name));
        // exact match
        if (name === k || syns.includes(k)) return 0;
        // prefix match
        if (name.startsWith(k) || syns.some(s => s.startsWith(k))) return 1;
        return 2;
      };

      return matches.sort((a, b) => {
        const sa = score(a); const sb = score(b);
        if (sa !== sb) return sa - sb;
        return a.name.localeCompare(b.name, undefined, {sensitivity: 'base'});
      });
    };

    onMounted(() => {
        useCredentialsStore().getItems();
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
    show-options-on-focus
    show-options-when-empty
    :search-function="searchCredentials"
  >
    <template #fixedBottomOption>
      <div class="text-sm">
        Don't see your credential? <button class="link" @click="initNewCredential">
          Create a new one.
        </button>
      </div>
    </template>
  </SearchSelect>
  <teleport to="body">
    <modal-dialog v-model="showCreateForm" title="Add a new credential">
      <CredentialCreateForm
        :starter-string="searchText"
        @saved="handleNewCredential"
        @canceled="cancelNewCredential"
      />
    </modal-dialog>
  </teleport>
</template>
