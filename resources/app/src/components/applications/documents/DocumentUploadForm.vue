<template>
    <div>
        <!-- <pre>{{documentTypes}}</pre> -->
        <h4 class="text-xl font-semibold pb-2 border-b mb-4">Upload document {{documentType.long_name}}</h4>

        <input-row label="Document" :errors="errors.file">
            <input type="file" ref="fileInput">
        </input-row>
        
        <input-row label="Date Received" type="date" v-model="newDocument.date_received" :errors="errors.date_receoved"></input-row>
        
        <input-row label="Date Reviewed" type="date" v-model="newDocument.date_reviewed" :errors="errors.date_reviewed"></input-row>

        <button-row>
            <button class="btn white" @click="cancel">Cancel</button>
            <button class="btn blue" @click="saveDocument">Save</button>
        </button-row>
    </div>
</template>
<script>
import { mapState } from 'vuex';
import {formatDate} from '../../../date_utils'
import is_validation_error from '../../../http/is_validation_error';

export default {
    props: {
        application: {
            type: Object,
            required: true
        },
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
            newDocument: {
                file: null,
                date_received: formatDate(new Date()),
                date_reviewed: null,
                step: this.step,
                document_type_id: this.documentTypeId,
            },
            errors: {},
        }
    },
    computed: {
        ...mapState({
            documentTypes: state => state.doctypes.items
        }),
        isReviewed () {
            return Boolean(this.newDocument.date_reviewed)
        },
        documentType () {
            if (this.documentTypes.length == 0) {
                return {};
            }
            // return this.documentTypes. 
            return this.$store.getters['doctypes/getItemById'](this.documentTypeId)
        }
    },
    methods: {
        async saveDocument() {
            try {
                let data = new FormData();
                Object.keys(this.newDocument)
                    .forEach(key => {
                        const val = this.newDocument[key]
                        if (val == null) return;
                        data.append(key, val);
                    })
                data.append('file', this.$refs.fileInput.files[0]);

                await this.$store.dispatch('applications/addDocument', 
                        {application: this.application, documentData: data}
                    )

                this.clearForm();
                this.$emit('saved');
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                    return;
                }
            }

        },
        cancel() {
            this.clearForm();
            this.$emit('canceled');
        },
        clearForm() {
            this.initNewDocument();
            this.clearErrors();
        },
        initNewDocument() {
            this.$refs.fileInput.value = null;
            this.newDocument = {
                file: null,
                date_received: formatDate(new Date()),
                date_reviewed: null,
                step: this.step,
                document_type_id: this.documentTypeId,
            }
        },
        clearErrors () {
            this.errors = {}
        }
    },
    mounted() {
        this.$store.dispatch('doctypes/getItems');
    }
}
</script>