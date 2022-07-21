<script setup>
    import { ref, inject } from 'vue'
    import CommentSummary from '../CommentSummary.vue';

    const commentManager = inject('commentManager')

    const showJudgementDialog = ref(true)
    const initJudgement = () => {
        showJudgementDialog.value = true
    }
    const commitJudgement = () => {
        showJudgementDialog.value = false
    }
    const cancelJudgement = () => {
        showJudgementDialog.value = false
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
        <div class="pt-2 flex space-x-2 items-center">
            <div class="lg:w-3/5">
                <button class="block btn btn-lg blue" @click="initJudgement">Approve or request revisions</button>
            </div>
            <div class="flex space-x-2 items-center p-2 bg-gray-200 rounded-lg lg:w-2/5">
                <icon-arrow-down class="text-blue-600 flex-shrink-0" width="30" height="30" /> 
                <div>Reply to comments or create comments, suggestions, and required revisions in the comments sections below.</div>
                <icon-arrow-down class="text-blue-600 flex-shrink-0" width="30" height="30" /> 
            </div>
        </div>
        <teleport to='body'>
            <modal-dialog 
                v-model="showJudgementDialog"
                title="Approve or request revisions"
            >
                <div v-if="commentManager.commentsForEp.length > 0">
                    <h3>The following comments will be sent to the expert panel:</h3>
                    <CommentSummary :comments="commentManager.commentsForEp" />
                </div>

                

                <template v-slot:footer>
                    <button-row @submitted="commitJudgement" @canceled="cancelJudgement"></button-row>
                </template>
            </modal-dialog>
        </teleport>
    </div>
</template>