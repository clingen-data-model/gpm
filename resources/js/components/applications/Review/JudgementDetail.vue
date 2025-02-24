<script setup>
    import {judgementColor} from '@/composables/judgement_utils.js'
    import { api } from '@/http'
    import { titleCase } from '@/string_utils.js';
    import {computed, inject, ref} from 'vue'
    import {useStore} from 'vuex'
    import JudgementForm from './JudgementForm.vue';

    const emits = defineEmits(['deleted']);
    const store = useStore();
    const latestSubmission = inject('latestSubmission')
    const group = computed(() => store.getters['groups/currentItemOrNew']);
    const user = computed(() => store.getters.currentUser);
    const userJudgement = computed({
        get() {
            if (latestSubmission.value && latestSubmission.value.judgements) {
                return latestSubmission.value.judgements.find(j => j.person_id === user.value.person.id)
            }

            return null;
        },
    })
    const badgeVariant = computed(() => judgementColor (userJudgement.value.decision))

    const showJudgementDialog = ref(false);
    const initUpdate = () => {
        showJudgementDialog.value = true;
    }

    const showDeleteDialog = ref(false);
    const initDelete = () => {
        showDeleteDialog.value = true;
    }
    const deleteJudgement = async () => {
        await api.delete(`/api/groups/${group.value.uuid}/application/judgements/${userJudgement.value.id}`)
        showDeleteDialog.value = false;
        emits('deleted');
    }
</script>

<template>
  <div v-if="userJudgement">
    <h3>
      Your Decision:
      <badge :color="badgeVariant" size="lg">
        {{ titleCase(userJudgement.decision) }}
      </badge>
    </h3>
    <div v-if="userJudgement.notes" class="mt-2">
      <p><strong>Notes: </strong>{{ userJudgement.notes }}</p>
    </div>
    <div class="flex space-x-2">
      <button class="btn btn-xs" @click="initUpdate">
        Change Decision
      </button>
      <button class="btn btn-xs" @click="initDelete">
        Cancel Decision
      </button>
    </div>

    <teleport to="body">
      <modal-dialog v-model="showJudgementDialog" title="Update Your Decision">
        <JudgementForm
          v-model="userJudgement"
          @saved="showJudgementDialog = false"
          @canceled="showJudgementDialog = false"
        />
      </modal-dialog>

      <modal-dialog v-model="showDeleteDialog" title="Delete your decision for this applicaiton">
        You are about to delete your decision for this applicaiton.
        This cannot be undone.
        Are you sure you want to continue?

        <template #footer>
          <button-row submit-text="Yes, Delete" @submitted="deleteJudgement" @canceled="showDeleteDialog = false" />
        </template>
      </modal-dialog>
    </teleport>
  </div>
</template>
