<script setup>
    import {ref, computed, inject} from 'vue';
    import {useStore} from 'vuex';
    import {api} from '@/http'
    import CommentSummary from '../CommentSummary.vue';
import {isValidationError} from '../../../http';

    const emits = defineEmits(['saved', 'canceled'])

    const commentManager = inject('commentManager')
    const store = useStore();
    const group = computed(() => store.getters['groups/currentItemOrNew'])

    const judgementOptions = [
        {label: 'Request Revisions', value: 'request-revisions'},
        {label: 'Approve after revisions', value: 'approve-after-revisions'},
        {label: 'Approve', value: 'approve'},
    ];

    const errors = ref({});
    const judgement = ref({});
    const judgementNotes = ref();

    const commitJudgement = async () => {
        try {
            await api.post(
                `/api/groups/${group.value.uuid}/application/judgement`, 
                {
                    judgement: judgement.value.value, 
                    judgement_notes: judgementNotes.value
                }
            );
            emits('saved')
            clearJudgementData();
        } catch (err) {
            if (isValidationError(err)) {
                errors.value = err.response.data.errors
            }
        }
    }
    const cancelJudgement = () => {
        clearJudgementData()
        emits('canceled')
    }

    const clearJudgementData = () => {
        judgement.value = undefined
        judgementNotes.value = undefined
    }
</script>

<template>
    <div>
        <div v-if="commentManager.commentsForEp.length > 0" class="mt-2">
            <h3>The following comments will be sent to the expert panel:</h3>
            <CommentSummary :comments="commentManager.commentsForEp" />
        </div>
        <hr>
        <input-row :errors="errors.judgement" vertical>
            <template v-slot:label>
                <h3>How should we proceed?</h3>
            </template>
            <radio-button-group 
                v-model="judgement" 
                :options="judgementOptions" 
                labelAttribute="label" 
                size="lg" 
                vertical
            />
        </input-row>

        <input-row 
            v-model="judgementNotes" 
            label="Notes for the expert panel" 
            type="large-text"
            :errors="errors.notes"
            vertical
        />
        <div class="flex px-4 space-x-4 items-center p-2 bg-gray-100 rounded-lg">
            <icon-exclamation class="text-blue-600 flex-shrink-0" width="30" height="30" /> 
            <div>
                Reply to or create comments, suggestions, and required revisions in the comments section on the main screen.
            </div>
        </div>
        <button-row @submitted="commitJudgement" @canceled="cancelJudgement"></button-row>
    </div>
</template>