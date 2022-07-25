<script setup>
    import { ref, computed, inject } from 'vue'
    import { useStore } from 'vuex'
    import CommentSummary from '../CommentSummary.vue';
    import JudgementForm from './JudgementForm.vue';
    import JudgementDetail from './JudgementDetail.vue';

    const commentManager = inject('commentManager');
    const latestSubmission = inject('latestSubmission')

    const emits = defineEmits(['deleted', 'saved'])

    const store = useStore();
    const group = computed(() => store.getters['groups/currentItemOrNew'])

    const showJudgementDialog = ref(false)
    const initJudgement = () => {
        showJudgementDialog.value = true
    }
    const hasMadeJudgment = computed(() => {
        return latestSubmission.value.judgements
            && latestSubmission.value.judgements.length > 0
            //  && latestSubmission.judgements.filter(j => j.person_id == store.getters.currentUser.person.id).length > 0;
    })

    const handleSave = (newJudgement) => {
        showJudgementDialog.value = false;
        emits('saved', newJudgement);
        
    }
</script>
<template>
    <div class="flex flex-col space-y-2 screen-block">
        <div v-if="commentManager.commentsForEp.length > 0">
            <h3>Comments for the Expert Panel</h3>
            <CommentSummary :comments="commentManager.commentsForEp" />
        </div>
        <div v-if="commentManager.openInternal.length > 0">
            <h3>Internal Comments</h3>
            <CommentSummary :comments="commentManager.openInternal" />
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