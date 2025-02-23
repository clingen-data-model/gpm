<script setup>
    import {api} from '@/http'
    import {isValidationError} from '@/http';
    import {computed, inject, ref, watch} from 'vue';
    import {useStore} from 'vuex';
    import CommentSummary from '../CommentSummary.vue';

    const props = defineProps({
        modelValue: {
            type: Object,
            default: () => ({decision: null, notes: null})
        }
    })
    const emits = defineEmits(['saved', 'canceled', 'update:modelValue'])

    const commentManager = inject('commentManager')
    const store = useStore();
    const group = computed(() => store.getters['groups/currentItemOrNew'])

    const judgementOptions = [
        'request-revisions',
        'approve-after-revisions',
        'approve',
    ];

    const errors = ref({});

    const judgement = ref({
        judgement: null,
        notes: null,
    });

    const syncJudgement = (val) => {
        judgement.value = val;
    }

    watch(() => props.modelValue, to => {
        if (to) {
            syncJudgement(to);
        }
    }, {immediate: true});

    const clearJudgementData = () => {
        if (props.modelValue) {
            syncJudgement(props.modelValue);
            return;
        }
        judgement.value = {}
    }
    
    const createJudgement = async () => {
        return  api.post(
            `/api/groups/${group.value.uuid}/application/judgements`,
            {
                decision: judgement.value.decision,
                notes: judgement.value.notes
            }
        ).then(rsp => {
            emits('saved', rsp.data);
            clearJudgementData();
            return rsp.data;
        });
    }

    const updateJudgement = async () => {
        const url = `/api/groups/${group.value.uuid}/application/judgements/${judgement.value.id}`;
        return api.put(url, judgement.value)
            .then(rsp => {
                emits('saved', rsp.data);
                return rsp.data
            });

    }

    const commitJudgement = async () => {
        try {
            return judgement.value.id
                ? await updateJudgement()
                : await createJudgement();
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
</script>

<template>
    <div>
        <div v-if="commentManager.commentsForEp.length > 0" class="mt-2">
            <h3>The following comments will be sent to the expert panel:</h3>
            <CommentSummary :comments="commentManager.commentsForEp" />
        </div>
        <hr>
        <input-row :errors="errors.decision" vertical>
            <template v-slot:label>
                <h3>How should we proceed?</h3>
            </template>
            <radio-button-group
                v-model="judgement.decision"
                :options="judgementOptions"
                labelAttribute="label"
                size="lg"
                vertical
            />
        </input-row>

        <input-row
            v-model="judgement.notes"
            label="Other notes for the expert panel"
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
