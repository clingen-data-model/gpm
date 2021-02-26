<template>
    <div>
        <h4 class="text-xl font-semibold pb-2 border-b mb-4">Upload document type {{documentTypeId}}</h4>

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
import {formatDate} from '../../date_utils'
import is_validation_error from '../../http/is_validation_error';

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
                document_category_id: this.documentTypeId
        },
            errors: {} 
        }
    },
    methods: {
        async saveDocument() {
            try {
                let data = new FormData();
                console.log(Object)
                Object.keys(this.newDocument)
                    .forEach(key => {
                        const val = this.newDocument[key]
                        if (val == null) return;
                        data.append(key, val);
                    })
                data.append('file', this.$refs.fileInput.files[0]);

                console.info('formData', data);

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
            this.$emit('closed');
        },
        clearForm() {
            this.initNewDocument();
            this.clearErrors();
        },
        initNewDocument() {
            this.newDocument = {
                file: null,
                date_received: formatDate(new Date()),
                date_reviewed: null,
                step: this.step,
                document_category_id: this.documentTypeId
            }
        },
        clearErrors () {
            this.errors = {}
        }
    }
}
</script>