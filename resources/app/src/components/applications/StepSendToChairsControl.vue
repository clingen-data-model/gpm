<script setup>
    import {ref, computed, useAttrs} from 'vue'
    import commentManagerFactory from '@/composables/comment_manager.js'
    import ReviewCommentAnonymous from '@/components/expert_panels/ReviewCommentAnonymous.vue'
    import { titleCase } from '@/utils';
    import {api} from '@/http';

    const attrs = useAttrs();
    const props = defineProps({
        group: {
            type: Object,
            required: true
        }
    })
    const emits = defineEmits(['sentToChairs'])
    
    const commentManager = commentManagerFactory('App\\Modules\\Group\\Models\\Group', props.group.id)
    const additionalComments = ref();    
    const commentsBySection = computed(() => {
        const sections = {}
        commentManager.openComments.value.forEach(c => {
            const section = c.metadata.section || 'general'
            if (!sections[c.metadata.section]) {
                sections[section] = [];
            }
            sections[section].push(c)
        })
        return sections
    });

    
    const showModal = ref(false)
    const initSendToChairs = () => {
        commentManager.getComments();
        showModal.value = true
    }
    const sendToChairs = async () => {
        await api.post(
            `/api/groups/${props.group.uuid}/command`, 
            {
                command: 'app.modules.group.actions.applicationSendToChairs',
                additionalComments: additionalComments.value || null
            }
        )
        emits('sentToChairs');
        showModal.value = false;
    }
    const cancel = () => {
        showModal.value = false;
    }
</script>

<template>
    <div>
        <button 
            v-if="group.expert_panel.hasPendingSubmissionForCurrentStep"
            class="btn btn-lg w-full" 
            @click="initSendToChairs"
            v-bind="attrs"
        >
            Send to Chairs for Review
        </button>

        <teleport to='body'>
            <modal-dialog v-model="showModal" title="Send to CDWG OC co-chairs for approval">
                <p>
                    Sending the application to the chairs for approval will send a message to the <em>cdwgoc_approvals</em> with a PDF of the application and the Core Group comments shown below.
                </p>
                <input-row 
                    v-model="additionalComments" 
                    vertical 
                    type="large-text"
                    label="Additional notes for chairs"
                    labelClass="font-bold"
                >
                    <template v-slot:after-input>
                        <note>This is not intended for additional comments about the applicaiton. This is intended for notes about process, timeing, etc.  To add comments related to the content of the application click 'Cancel' and add notes alongside the application.</note>
                    </template>
                </input-row>

                <h3>Comments from the Core Group</h3>
                <table class="mt-2">
                    <tr v-for="(comments, section) in commentsBySection" :key="section">
                        <td class=" border-none">
                            <h4>{{titleCase(section)}}</h4>
                        </td>
                        <td class=" border-none">
                            <ReviewCommentAnonymous 
                                v-for="comment in comments" 
                                :key="comment.id" 
                                :comment="comment" 
                                class="mb-4"
                            />
                        </td>
                    </tr>
                    <tr>
                        <td class="border-none"></td>
                        <td class="border-none">
                            <note>
                                To make changes to comments click 'Cancel' and update alongside the application.
                            </note>
                        </td>
                    </tr>
                </table>
                
                <template v-slot:footer>
                    <button-row @submitted="sendToChairs" @canceled="cancel" submit-text="Send to Chairs"></button-row>
                </template>
            </modal-dialog>
        </teleport>
    </div>
</template>
