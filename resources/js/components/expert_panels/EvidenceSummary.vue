<template>
    <div>
        <transition mode="out-in" name="fade">
            <div v-if="!editing"
                class="p-2 border rounded"
            >
                <header class="flex justify-between">
                    <h4>{{summary.gene.gene_symbol}} - {{summary.variant}}</h4>
                    <dropdown-menu :hide-cheveron="true" class="relative" v-if="canEdit">
                        <template v-slot:label>
                            <button class="btn btn-xs">&hellip;</button>
                        </template>
                        <dropdown-item @click="edit()">Edit</dropdown-item>
                        <dropdown-item @click="confirmDelete()">Delete</dropdown-item>
                    </dropdown-menu>
                </header>
                <p>{{summary.summary}}</p>
                <a class="link" :href="summary.vci_url" v-if="summary.vci_url" target="_blank">
                    View in the VCI
                </a>
            </div>
            <EvidenceSummaryForm 
                v-else 
                :summary="summary" 
                :group="group"
                @saved="handleSaved"
                @canceled="cancelEdit"
            ></EvidenceSummaryForm>
        </transition>
        <teleport to="body">
            <modal-dialog v-model="showDeleteConfirm" title="You are about to delete an example evidence summary.">
                You are about to delete an evidence summary for {{summary.gene.gene_symbol}} - {{summary.variant}}.  Are you sure you want to continue?
                <button-row @submit="deleteSummary" @cancel="cancelDelete" submit-text="Delete Summary"></button-row>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import api from '@/http/api';
import EvidenceSummaryForm from '@/components/expert_panels/EvidenceSummaryForm.vue';
export default {
  components: { EvidenceSummaryForm },
    name: 'EvidenceSummary',
    props: {
        summary: {
            required: true,
            type: Object
        },
        group: {
            required: true,
            type: Object
        },
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: [
        'edit',
        'saved',
        'deleted',
    ],
    data() {
        return {
            editing: false,
            showDeleteConfirm: false
        }
    },
    computed: {
        canEdit () {
            return this.hasAnyPermission(['ep-applications-manage', ['application-edit', this.group]]) 
                && !this.readonly
        }
    },
    methods: {
        handleSaved(newSummary) {
            this.$emit('saved', newSummary);
            this.editing = false;
        },
        edit () {
            this.editing = true;
        },
        cancelEdit () {
            this.editing = false;
        },
        confirmDelete () {
            this.showDeleteConfirm = true;
        },
        async deleteSummary () {
            try {
                await api.delete(`/api/groups/${this.group.uuid}/application/evidence-summaries/${this.summary.id}`);
                this.$emit('deleted', this.summary)
            } catch (error) {
                console.error(error);
            }
            this.showDeleteConfirm = false;
        },
        cancelDelete() {
            this.showDeleteConfirm = false;
        },
    }
}
</script>