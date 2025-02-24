<script>
import { formatDate } from '../../../date_utils'
import is_validation_error from '../../../http/is_validation_error'
import RemoveButton from '../../buttons/RemoveButton'
import DocumentEditForm from './DocumentEditForm'

export default {
    components: {
        DocumentEditForm,
        RemoveButton
    },
    props: {
        documentTypeId: {
            type: Number,
            required: true
        },
        step: {
            type: Number,
            required: false,
            default: null
        }        
    },
    data() {
        return {
            showEditForm: false,
            editDateReceived: false,
            newDateReceived: null,
            editDateReviewed: false,
            newDateReviewed: null
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        application () {
            return this.group.expert_panel;
        },
        finalDocument() {
            return this.application.finalDocumentOfType(this.documentTypeId);
        },
        firstDocument() {
            return this.application.firstDocumentOfType(this.documentTypeId);
        },
        finalDateReviewed () {
            if (!this.finalDocument.date_reviewed) {
                return '--'
            }
            return formatDate(this.finalDocument.date_reviewed)
        },
        firstDateReceived () {
            if (!this.firstDocument.date_received) {
                return '--'
            }
            return formatDate(this.firstDocument.date_received)
        },
    },
    methods: {
        initEditReceived(){
            this.editDateReceived = true;
            this.newDateReceived = this.firstDateReceived
        },
        async saveDateReceived() {
            try {
                await this.$store.dispatch('applications/updateDocumentInfo', {
                        application: this.application, 
                        document: {
                            uuid: this.firstDocument.uuid,
                            date_received: this.newDateReceived, 
                            date_reviewed: this.firstDocument.date_reviewed
                        }
                    });
                    this.editDateReceived = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    // eslint-disable-next-line no-console
                    console.info('saveDateReceived errors: ', error.response.data.errors)
                }
            }
        },
        initEditReviewed(){
            this.editDateReviewed = true;
            this.newDateReviewed = this.finalDateReviewed
        },
        async saveDateReviewed() {
            try {
                await this.$store.dispatch('applications/updateDocumentInfo', {
                        application: this.application, 
                        document: {
                            uuid: this.finalDocument.uuid,
                            date_reviewed: this.newDateReviewed, 
                            date_received: this.finalDocument.date_received
                        }
                    });
                    this.editDateReviewed = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    // eslint-disable-next-line no-console
                    console.info('saveDateReviewd errors: ', error.response.data.errors)
                }
            }
        },
    }
}
</script>
<template>
    <div>
        <dictionary-row label="Final Document">
            <div>
                <a :href="finalDocument.download_url" class="text-blue-500 underline" v-if="finalDocument.filename">
                    {{ finalDocument.filename }}
                </a>
                <span v-else>none on file</span>
            </div>
        </dictionary-row>
        
        <dictionary-row label="Date Received">
            <div v-show="!editDateReceived">
                {{ firstDateReceived }}
                <edit-icon-button @click="initEditReceived"></edit-icon-button>
            </div>
            <div v-show="editDateReceived">
                <date-input v-model="newDateReceived" class="inline-block"></date-input>
                <button class="btn btn-sm blue" @click="saveDateReceived">Save</button>
                <RemoveButton class="ml-1" @click="editDateReceived = false"></RemoveButton>
            </div>
        </dictionary-row>

        <!-- <dictionary-row label="Date Reviewed">
            <div v-show="!editDateReviewed">
                {{finalDateReviewed}}
                <edit-icon-button @click="initEditReviewed"></edit-icon-button>
            </div>
            <div v-show="editDateReviewed">
                <date-input v-model="newDateReviewed" class="inline-block"></date-input>
                <button class="btn btn-sm blue" @click="saveDateReviewed">Save</button>
                <remove-button class="ml-1" @click="editDateReviewed = false"></remove-button>
            </div>
        </dictionary-row> -->

        <modal-dialog v-model="showEditForm">
            <DocumentEditForm
                :document="finalDocument"
                :application="application"
                @canceled="showEditForm = false"
                @saved="showEditForm = false"
            ></DocumentEditForm>
        </modal-dialog>

    </div>
</template>