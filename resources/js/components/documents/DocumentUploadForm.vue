<script>
import { mapState } from 'vuex';
import { isValidationError } from '@/http';

export default {
    props: {
        saveFunction: {
            type: Function,
            required: true
        }
    },
    data() {
        return {
            newDocument: {
                file: null,
                document_type_id: this.documentTypeId,
                notes: null
            },
            errors: {},
        }
    },
    computed: {
        ...mapState({
            documentTypes: state => state.doctypes.items
        }),
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        },
        application () {
            return this.group.expert_panel
        },
        isReviewed () {
            return Boolean(this.newDocument.date_reviewed)
        },
        documentType () {
            if (this.documentTypes.length === 0) {
                return {};
            }
            // return this.documentTypes. 
            return this.$store.getters['doctypes/getItemById'](this.documentTypeId)
        }
    },
    mounted() {
        this.$store.dispatch('doctypes/getItems');
    },
    methods: {
        async save() {
            try {
                const data = new FormData();
                Object.keys(this.newDocument)
                    .forEach(key => {
                        const val = this.newDocument[key]
                        if (val == null) return;
                        data.append(key, val);
                    })
                data.append('file', this.$refs.fileInput.files[0]);
                await this.saveFunction(data);

                this.clearForm();
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors
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
                document_type_id: this.documentTypeId,
                notes: null
            }
        },
        clearErrors () {
            this.errors = {}
        }
    }
}
</script>
<template>
    <form-container>
        <input-row label="Document" :errors="errors.file">
            <input ref="fileInput" type="file">
        </input-row>

        <input-row label="Type" :errors="errors.document_type_id">
            <select v-model="newDocument.document_type_id">
                <option :value="null">Select...</option>
                <option v-for="type in documentTypes" :key="type.id" :value="type.id">
                    {{ type.long_name }}
                </option>
            </select>
        </input-row>

        <input-row :errors="errors.notes" label="Notes">
            <textarea v-model="newDocument.notes" name="notes" cols="30" rows="10"></textarea>
        </input-row>
        
        <button-row>
            <button class="btn white" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Save</button>
        </button-row>
    </form-container>
</template>