<script>
import configs from '@/configs.json'
import {isValidationError} from '@/http';
import {formatDate} from '../../../date_utils'

const documentsTypes = configs.documentsTypes;

export default {
    props: {
        documentTypeId: {
            type: Number,
            required: true
        },
        step: {
            type: Number,
            required: false,
            default: null
        },
        showNotes: {
            type: Boolean,
            default: true
        }
    },
    emits: [
        'saved',
    ],
    data() {
        return {
            newDocument: {
                file: null,
                date_received: new Date().toISOString(),
                date_reviewed: null,
                step: this.step,
                document_type_id: this.documentTypeId,
                notes: null
            },
            errors: {},
        }
    },
    computed: {
        docTypes () {
            return documentsTypes;
        },
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
            if (Object.values(this.docTypes).length === 0) {
                return {};
            }
            return Object.values(this.docTypes).find(dt => dt.id = this.documentTypeId)
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
                await this.$store.dispatch('groups/addApplicationDocument', 
                        {group: this.group, data}
                    )

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
                date_received: formatDate(new Date()),
                date_reviewed: null,
                step: this.step,
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
    <h2 class="pb-2 border-b mb-4">
      Upload {{ titleCase(documentType.long_name) }}
    </h2>

    <input-row label="Document" :errors="errors.file">
      <input ref="fileInput" type="file">
    </input-row>
        
    <input-row 
      v-if="showNotes" 
      v-model="newDocument.date_received" 
      label="Date Received" 
      type="date"
      :errors="errors.date_received"
    />

    <input-row v-if="showNotes" :errors="errors.notes" label="Notes">
      <textarea v-model="newDocument.notes" name="notes" cols="30" rows="5" />
    </input-row>
        
    <button-row>
      <button class="btn white" @click="cancel">
        Cancel
      </button>
      <button class="btn blue" @click="save">
        Save
      </button>
    </button-row>
  </form-container>
</template>