<script setup>
    import { ref, computed, inject } from 'vue'
    import { useStore } from 'vuex'
    import CommentSummary from '../CommentSummary.vue';
    import JudgementForm from './JudgementForm.vue';
    import JudgementDetail from './JudgementDetail.vue';
    import {judgementColor} from '@/composables/judgement_utils.js'

    const emits = defineEmits(['deleted', 'saved'])
    const commentManager = inject('commentManager');
    const latestSubmission = inject('latestSubmission')

    const store = useStore();
    // const group = computed(() => store.getters['groups/currentItemOrNew'])

    const showJudgementDialog = ref(false)
    const initJudgement = () => {
        showJudgementDialog.value = true
    }
    const hasMadeJudgment = computed(() => {
        return latestSubmission.value.judgements
            && latestSubmission.value.judgements.length > 0
            && latestSubmission.value.judgements.filter(j => j.person_id === store.getters.currentUser.person.id).length > 0;
    })

    const otherJudgements = computed(() => {
        if (!latestSubmission.value || !latestSubmission.value.judgements) {
            return [];
        }
        return latestSubmission.value.judgements.filter(j => j.person_id !== store.getters.currentUser.person.id)
    })

    const hasCommentsForEp = computed(() => commentManager.value.commentsForEp.length > 0)
    const hasInternalComments = computed(() => commentManager.value.openInternal.length > 0)

    const handleSave = (newJudgement) => {
        showJudgementDialog.value = false;
        emits('saved', newJudgement);

    }
</script>
<template>
    <div class="flex flex-col space-y-2 screen-block">
        <div class="xl:w-3/4 flex flex-col space-y-2 border-between-children">
            <div>
                <h3>Comments for the Expert Panel</h3>
                <div class="mb-2">
                    <CommentSummary v-if="hasCommentsForEp"
                        :comments="commentManager.commentsForEp"
                    />
                    <div v-else class="note">None</div>
                </div>
            </div>

            <div>
                <h3>Internal Comments</h3>
                <div class="mb-2">
                    <CommentSummary
                        v-if="hasInternalComments"
                        :comments="commentManager.openInternal"
                    />
                    <div v-else class="note">None</div>
                </div>
            </div>
            <div v-if="latestSubmission.notes_for_chairs">
                <h3>Other notes from the Core Group</h3>
                <p>
                    {{ latestSubmission.notes_for_chairs }}
                </p>
            </div>
            <div v-if="otherJudgements.length > 0">
                <h3>Other Approver's Judgements:</h3>
                <ul>
                    <li v-for="j in otherJudgements" :key="j.id" class="mt-2 ml-2">
                        <div class="text-lg">{{ j.person.name }}: <badge :color="judgementColor(j)">{{ titleCase(j.decision) }}</badge></div>
                        <p v-if="j.notes" class="ml-2 text-sm"><strong>Notes for EP: </strong>{{ j.notes }}</p>
                    </li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="pt-2 lg:flex lg:space-x-2 space-y-2 lg:space-y-0 items-start">
            <div class="lg:w-3/5">
                <button v-if="!hasMadeJudgment"
                    class="block btn btn-lg blue"
                    @click="initJudgement"
                >
                    Approve or request revisions
                </button>
                <JudgementDetail v-else @deleted="emits('deleted')" @saved="emits('saved')" />
            </div>
            <div class="flex space-x-2 items-center p-2 bg-gray-100 rounded-lg lg:w-2/5">
                <icon-arrow-down class="text-blue-600 flex-shrink-0" width="30" height="30" />
                <div>Reply to or create comments, suggestions, and required revisions in the comments sections below.</div>
                <icon-arrow-down class="text-blue-600 flex-shrink-0" width="30" height="30" />
            </div>
        </div>
        <teleport to='body'>
            <modal-dialog
                v-model="showJudgementDialog"
                title="Approve or request revisions"
            >
                <JudgementForm @saved="handleSave" @canceled="showJudgementDialog = false" />
            </modal-dialog>
        </teleport>
    </div>
</template>
<style scoped>
    .border-between-children > * {
        @apply border-b border-gray-100 pb-1 mb-1;
    }
    .border-between-children > *:last-child {
        @apply border-b-0 pb-0 mb-0;
    }
</style>
