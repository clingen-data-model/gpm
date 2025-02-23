<template>
    <div>
        <table v-if="documents.length > 0" class="table">
            <thead>
                <tr>
                    <th>File</th>
                    <th v-if="docTypeIsArray">Type</th>
                    <th>uploaded</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="doc in documents" :key="doc.id" class="p-1 border">
                    <td>
                        <a :href="doc.download_url">{{doc.filename}}</a>
                        <span class="note"> (v. {{doc.version}})</span>
                    </td>
                    <td v-if="docTypeIsArray">{{doc.type.long_name}}</td>
                    <td>{{formatDate(doc.created_at)}}</td>
                    <td>
                        <dropdown-menu hideCheveron>
                            <template #label> <button class="btn btn-xs">&hellip;</button></template>
                            <dropdown-item @click="initDownload(doc)">Download</dropdown-item>
                            <dropdown-item @click="initDelete(doc)"  v-if="hasAnyPermission(['ep-applications-manage', ['application-edit', group]])">Delete</dropdown-item>
                        </dropdown-menu>
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="!readonly">
            <input-row label="Document type"
                type="select"
                :options="filteredTypes"
                v-if="docTypeIsArray"
                v-model="newDocument.document_type_id"
            />

            <input-row label="Document" :errors="errors.file">
                <input type="file" ref="fileInput">
            </input-row>
            <button class="btn blue" @click="save">Upload</button>
        </div>
        <teleport to='body'>
             <modal-dialog
                v-model="showDeleteConfirmation"
                :title="`You are about to delete ${activeDocument.filename}`"
            >
                <div v-if="activeDocument">
                    <p v-if="activeDocument.is_final" class="mb-3">
                        This version has been tagged as the final version of the document.
                    </p>
                    <p>Are you sure you want to continue?</p>

                    <button-row
                        submit-text="Delete Document"
                        @canceled="cancelDelete"
                        @submitted="commitDelete"
                    >
                    </button-row>
                </div>
            </modal-dialog>
        </teleport>


    </div>
</template>
<script>
import config from '@/configs.json'
import {isValidationError} from '@/http';
import {formatDate} from '../../../date_utils'

const documentsTypes = config.documentsTypes;

export default {
    props: {
        documentTypeId: {
            type: [Number, Array],
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
        },
        readonly: {
            type: Boolean,
            default: false
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
                step: this.step,
            },
            errors: {},
            activeDocument: {},
            showDeleteConfirmation: false
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        },
        documents () {
            return this.group.documents.filter(d => {
                if (this.docTypeIsArray) {
                    return this.documentTypeId.includes(d.document_type_id)
                }
                return d.document_type_id === this.documentTypeId
            })
        },
        docTypeIsArray () {
            return Array.isArray(this.documentTypeId);
        },
        filteredTypes () {
            if (this.docTypeIsArray) {
                return Object.values(documentsTypes)
                        .filter(dt => this.documentTypeId.includes(dt.id))
                        .map(dt => ({label: dt.long_name, value: dt.id}));
            }
            return [];
        },
        document_type_id () {
            return (this.docTypeIsArray) ? null : this.documentTypeId;
        }
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
                data.append('document_type_id', this.documentTypeId);
                await this.$store.dispatch('groups/addApplicationDocument', {group: this.group, data});

                this.clearForm();
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
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
                notes: null
            }
        },
        clearErrors () {
            this.errors = {}
        },
        initDownload (item) {
            window.location = item.download_url;
        },
        initDelete (item) {
            this.activeDocument = item;
            this.showDeleteConfirmation = true;
        },
        cancelDelete () {
            this.showDeleteConfirmation = false;
            this.resetActiveDocument();
        },
        async commitDelete() {
            await this.$store.dispatch('groups/deleteDocument', {group: this.group, document: this.activeDocument})
            this.showDeleteConfirmation = false;
            this.resetActiveDocument();
        },
        resetActiveDocument () {
            this.activeDocument = {type: {}}
        }
    },
    mounted() {
        this.$store.dispatch('groups/getDocuments', this.group);
    }
}
</script>
