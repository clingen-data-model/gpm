<script setup>
    import { ref, inject } from 'vue'

    const commentManager = inject('commentManager')

    const showJudgementDialog = ref(false)
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
    <div class="flex flex-col space-y-2">

        <div class="screen-block flex space-x-2 items-center">
            <button class="block btn btn-lg">Comment on the Application in General</button>
            <button class="block btn btn-lg blue" @click="initJudgement">Approve or request revisions</button>
        </div>
        <teleport to='body'>
            <modal-dialog 
                v-model="showJudgementDialog"
                title="Approve or request revisions"
            >
                <pre>{{commentManager.comments}}</pre>

                <template v-slot:footer>
                    <button-row @submitted="commitJudgement" @canceled="cancelJudgement"></button-row>
                </template>
            </modal-dialog>
        </teleport>
    </div>
</template>