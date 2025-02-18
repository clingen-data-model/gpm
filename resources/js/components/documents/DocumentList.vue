<template>
    <div>
        <slot name="heading">
            <div class="flex justify-between py-2">
                <div class="flex space-x-2">
                    <label>
                        Search:&nbsp;<input type="text" placeholder="Filter" v-model="keyword" class="sm">
                    </label>
                    <label>
                        Type: &nbsp;
                        <select v-model="filterType">
                            <option :value="null">Any</option>
                            <option 
                                v-for="type in documentTypes"
                                :key="type.id"
                                :value="type.id"
                            >
                                {{type.long_name}}
                            </option>
                        </select>
                    </label>
                </div>
                <button class="btn btn-xs" @click="initUpload" v-if="canManage">Upload</button>
            </div>
        </slot>
        <data-table 
            :fields="fields" 
            :data="filteredDocuments" 
            v-model:sort="sort"
            :detailRows="true"
        >
            <template v-slot:cell-cheveron="{item}">
                <button 
                    v-if="item.metadata"
                    @click.stop="toggleItemDetails(item)" 
                    class="w-9 align-center block -mx-3"
                >
                    <icon-cheveron-right v-if="!item.showDetails" class="m-auto cursor-pointer" />
                    <icon-cheveron-down v-if="item.showDetails" class="m-auto cursor-pointer" />
                </button>
            </template>
            <template #cell-filename="{item}">
                {{item.filename}}
                <span class="note">
                    <em v-if="item.type.is_versioned && item.version">(version {{item.version}})</em>
                    <em v-if="item.is_final"> FINAL</em>
                </span>
            </template>
            <template v-slot:cell-actions="{item}">
                <dropdown-menu hideCheveron>
                    <template #label> <button class="btn btn-xs">&hellip;</button></template>
                    <dropdown-item @click="initDownload(item)">Download</dropdown-item>
                    <dropdown-item @click="initUpdate(item)" v-if="canManage">Update</dropdown-item>
                    <dropdown-item @click="initDelete(item)" v-if="canManage">Delete</dropdown-item>
                </dropdown-menu>
            </template>
            <template v-slot:detail="{item}">
                <div class="px-4 pb-4 border">
                    <object-dictionary :obj="item.metadata" />
                    <div class="flex space-x-2 pt-2 border-t" v-if="canManage">                    
                        <button class="btn btn-xs" @click="initDownload(item)">Download</button>
                        <button class="btn btn-xs" @click="initUpdate(item)">Update</button>
                        <button class="btn btn-xs red" @click="initDelete(item)">Delete</button>
                    </div>
                </div>
            </template>
        </data-table>

        <teleport to="body">
            <modal-dialog v-model="showEditForm" :title="`Update ${activeDocument.filename}`">
                <document-edit-form 
                    :document="activeDocument"
                    :save-function="this.commitUpdate"
                    @saved="$emit('updated')"
                />
            </modal-dialog>

            <modal-dialog v-model="showUploadForm" title="Upload a new document">
                <document-upload-form 
                    :save-function="this.commitCreate" 
                    @saved="$emit('updated')"
                />
            </modal-dialog>

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
import {api} from '@/http'
import DocumentUploadForm from './DocumentUploadForm.vue'
import DocumentEditForm from './DocumentEditForm.vue'

export default {
    name: 'DocumentList',
    components: {
        DocumentUploadForm,
        DocumentEditForm
    },
    emits: ['updated'],
    props: {
        documents: {
            type: Array,
            required: true
        },
        documentCreator: {
            type: Function,
            required: false
        },
        documentUpdater: {
            type: Function,
            required: false
        },
        documentDeleter: {
            type: Function,
            required: false
        },
        canManage: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            fields: [
                {
                    name: 'cheveron',
                    label: '',
                    sortable: false
                },
                {
                    name: 'filename',
                    label: 'File',
                    type: String,
                    sortable: true
                },
                {
                    name: 'type.long_name',
                    label: 'Type',
                    type: String,
                    sortable: true
                },
                {
                    name: 'notes',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'actions',
                    label: '',
                    type: String,
                    sortable: false,
                }
            ],
            sort: {
                field: 'filename',
                desc: false
            },
            keyword: null,
            filterType: null,
            documentTypes: [],
            activeDocument: {
                type: {}
            },
            showDeleteConfirmation: false,
            showEditForm: false,
            showUploadForm: false
        }
    },
    computed: {
        filteredDocuments () {
            let documents = [...this.documents];
            if (this.keyword) {
                const rx = new RegExp('.*'+this.keyword+'.*', 'i');
                documents = documents.filter(doc => {
                    return doc.filename.match(rx)
                        || (doc.notes && doc.notes.match(rx))
                        || doc.type.name.match(rx)
                });
            }
            if (this.filterType) {                                
                documents = documents.filter(doc => {
                    return doc.document_type_id == this.filterType
                })
            }
            return documents
        },
    },
    methods: {
        toggleItemDetails(item) {
            item.showDetails = !item.showDetails;
        },
        initDownload (item) {
            window.location = item.download_url;
        },
        initUpdate (item) {
            this.showEditForm = true; 
            this.activeDocument = item;
        },
        commitUpdate (data) {
            if (this.documentUpdater) {
                this.documentUpdater(data)
            } else {
                alert('we need a default document upload function');
            }
            this.$emit('updated');

            this.showUploadForm = false;
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
            if (this.documentDeleter) {
                this.documentDeleter(this.activeDocument);
            } else {
                alert('we need to add a default document deleter.');
            }
            this.showDeleteConfirmation = false;
            this.resetActiveDocument();
            this.$emit('updated');
        },
        initUpload() {
            this.showUploadForm = true;
        },
        commitCreate (data) {
            if (this.documentCreator) {
                this.documentCreator(data)
            } else {
                alert('we need a default document upload function');
            }

            this.$emit('updated');
            this.showUploadForm = false;
        },
        resetActiveDocument () {
            this.activeDocument = {type: {}}
        }
    },
    mounted () {
        api.get('/api/document-types')
            .then(response => this.documentTypes = response.data)
    }
}
</script>