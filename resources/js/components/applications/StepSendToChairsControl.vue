<script setup>
    import {api} from '@/http';
    import {inject, ref, useAttrs} from 'vue'
    import CommentSummary from './CommentSummary.vue';

    const props = defineProps({
        group: {
            type: Object,
            required: true
        }
    })
    const emits = defineEmits(['sentToChairs'])
    const attrs = useAttrs();
    const commentManager = inject('commentManager');
    const additionalComments = ref();
    
    const showModal = ref(false)
    const initSendToChairs = () => {

        commentManager.value.getComments();
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
      class="btn btn-lg w-full" 
      v-bind="attrs"
      @click="initSendToChairs"
    >
      Send to Chairs for Review
    </button>

    <teleport to="body">
      <modal-dialog v-model="showModal" title="Send to CDWG OC co-chairs for approval">
        <p>
          Sending the application to the chairs for approval will send a message to the <em>cdwgoc_approvals</em> with a PDF of the application and the Core Group comments shown below.
        </p>

        <h3>Comments from the Core Group</h3>
        <CommentSummary :comments="commentManager.openComments" />

        <input-row 
          v-model="additionalComments" 
          vertical 
          type="large-text"
          label="Additional notes for chairs"
          label-class="font-bold"
        >
          <template #after-input>
            <note>This is not intended for additional comments about the applicaiton. This is intended for notes about process, timeing, etc.  To add comments related to the content of the application click 'Cancel' and add notes alongside the application.</note>
          </template>
        </input-row>

                
        <template #footer>
          <button-row submit-text="Send to Chairs" @submitted="sendToChairs" @canceled="cancel" />
        </template>
      </modal-dialog>
    </teleport>
  </div>
</template>
