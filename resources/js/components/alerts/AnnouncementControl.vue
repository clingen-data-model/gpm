<script>
import {announcement, validationErrors, saveAnnouncement, cancelAnnouncement, fields} from '@/forms/announcement_form'
export default {
    name: 'AnnouncementControl',
    setup () {
        return {
            announcement,
            errors: validationErrors,
            saveAnnouncement,
            cancelAnnouncement,
            formFields: fields
        }
    },
    data() {
        return {
            showConfirmation: false,
            showForm: false
        }
    },
    computed: {
        notification () {
            return {
                data: {
                    message: this.announcement.message || 'NONE',
                    type: this.announcement.type || 'info',
                    markdown: true
                }
            }
        }
    },
    methods: {
        initAnnouncement () {
            this.showForm = true;
        },
        confirmSubmission () {
            this.showConfirmation = true;
        },
        async saveConfirmed () {
            this.showConfirmation = false;
            try {
                this.saveAnnouncement();
                this.showForm = false;
            } catch (error) {
                // eslint-disable-next-line no-alert
                alert(error.message)
            }
        }
    }
}
</script>
<template>
  <div>
    <button class="link" @click="initAnnouncement">
      Make an announcement
    </button>
    <teleport to="body">
      <modal-dialog v-model="showForm" title="Make an announcement">
        <data-form
          v-model="announcement"
          :fields="formFields"
          :errors="errors"
        />
        <h4>Preview:</h4>
        <notification-item :notification="notification" />

        <button-row submit-text="Save" @submitted="confirmSubmission" />
      </modal-dialog>
      <modal-dialog v-model="showConfirmation" title="Confirm Announcement">
        You are about to make the following announcement:
        <notification-item :notification="notification" />
        <button-row submit-text="Confirm" @submitted="saveConfirmed" />
      </modal-dialog>
    </teleport>
  </div>
</template>
