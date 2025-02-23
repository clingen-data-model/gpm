<template>
    <div>
        <button @click="initAnnouncement" class="link">Make an announcement</button>
        <teleport to='body'>
            <modal-dialog title="Make an announcement" v-model="showForm">
                <data-form
                    :fields="formFields"
                    v-model="announcement"
                    :errors="errors"
                ></data-form>
                <h4>Preview:</h4>
                <notification-item :notification="notification" />

                <button-row @submitted="confirmSubmission" submit-text="Save"></button-row>
            </modal-dialog>
            <modal-dialog title="Confirm Announcement" v-model="showConfirmation">
                You are about to make the following announcement:
                <notification-item :notification="notification" />
                <button-row @submitted="saveConfirmed" submit-text="Confirm"></button-row>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import {announcement, cancelAnnouncement, fields, saveAnnouncement, validationErrors} from '@/forms/announcement_form'
export default {
    name: 'AnnouncementControl',
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
    },
    setup () {
        return {
            announcement,
            errors: validationErrors,
            saveAnnouncement,
            cancelAnnouncement,
            formFields: fields
        }
    }
}
</script>
