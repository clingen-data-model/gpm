<script>
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error';
import EvidenceSummaryScopeStatus from './EvidenceSummaryScopeStatus.vue';


export default {
    name: 'EvidenceSummaryForm',
    components: { EvidenceSummaryScopeStatus },
    props: {
        group: {
            required: true,
            type: Object,
        },
        summary: {
            required: true,
            type: Object
        }
    },
    emits: [
        'saved',
        'canceled'
    ],
    data() {
        return {
            groupGenes: [],
            replacementGeneId: null,
            summaryClone: {gene: {}},
            errors: {}
        }
    },
    computed: {
        selectableGenes() {
            return this.groupGenes.filter(gene => !gene.deleted_at);
        },
        hasRemovedAssociation() {
            return !!(this.summary.id && (this.group.is_vcep || this.group.is_scvcep) && this.summary.gene?.deleted_at);
        },
        selectedGeneId: {
            get() { return this.hasRemovedAssociation ? this.replacementGeneId : this.summaryClone.gene_id; },
            set(value) {
                if (this.hasRemovedAssociation) this.replacementGeneId = value;
                else this.summaryClone.gene_id = value;
            },
        },
    },
    watch: {
        group: {
            immediate: true,
            handler () {
                if (this.group && this.group.id) {
                    this.getGroupGenes();
                }
            }
        },
        summary: {
            immediate: true,
            handler () {
                this.summaryClone = {...this.summary}
                this.replacementGeneId = null;
            }
        }
    },
    methods: {
        async save () {
            try {
                let url = `/api/groups/${this.group.uuid}/expert-panel/evidence-summaries`;
                let method = 'post';
                if (this.summaryClone.id) {
                    url += `/${this.summaryClone.id}`
                    method = 'put';
                }
                const data = { ...this.summaryClone };
                if (this.hasRemovedAssociation) {
                    delete data.gene_id;
                    if (this.replacementGeneId != null) data.gene_id = this.replacementGeneId;
                }
                const newSummary = await api({method, url, data})
                                            .then (response => response.data.data);

                this.$emit('saved', newSummary);
                this.$store.commit('pushSuccess', 'Saved example evidence summary');
                this.editing = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
            }
        },
        cancel () {
            this.initSummaryClone();
            this.$emit('canceled')
        },
        edit (summary) {
            summary.editing = true;
        },
        async getGroupGenes () {
            this.groupGenes = await api.get(`/api/groups/${this.group.uuid}/expert-panel/genes`)
                                .then(response => response.data);
        },
        initSummaryClone () {
            this.summaryClone = {gene: {}};
        }
    },
}
</script>
<template>
  <div>
    <div v-if="hasRemovedAssociation" class="mb-3 text-sm" data-testid="current-association">
      <div class="font-semibold">Current association:</div>
      <div>{{ summary.gene.gene_symbol }} — {{ summary.gene.mondo_id }} {{ summary.gene.disease_name }}</div>
      <EvidenceSummaryScopeStatus :summary="summary" :group="group" />
    </div>
    <div class="flex space-x-2">
      <input-row class="mt-0 mb-0" :vertical="true" :label="hasRemovedAssociation ? 'Change gene-disease' : 'Gene'" :errors="errors.gene_id">
        <select v-model="selectedGeneId">
          <option :value="null">
            {{ hasRemovedAssociation ? 'Select a current gene-disease...' : 'Select...' }}
          </option>
          <option
            v-for="gene in selectableGenes"
            :key="gene.id"
            :value="gene.id"
          >
            Gene: {{ gene.gene_symbol }} {{ gene.mondo_id ? ' - Disease: ' + gene.mondo_id : '' }} {{ gene.moi ? ' - MoI: ' + gene.moi : '' }}
          </option>
        </select>
      </input-row>
      <input-row
        v-model="summaryClone.variant"
        class="mt-0 mb-0"
        :vertical="true"
        label="Variant"
        :errors="errors.variant"
      />
      <input-row
        v-if="group.is_scvcep"
        v-model="summaryClone.assertion_id"
        class="mt-0 mb-0"
        :vertical="true"
        label="Assertion ID"
        :errors="errors.assertion_id"
        :required="true"
        :max-length="255"
      />
    </div>
    <input-row class="mt-0 mb-0" label="Summary" :vertical="true" :errors="errors.summary">
      <textarea v-model="summaryClone.summary" rows="5" class="w-full" />
    </input-row>
    <input-row v-if="group.is_vcep || group.is_scvcep"
      v-model="summaryClone.vci_url"
      class="mt-0"
      :label="group.is_vcep ? 'VCI URL' : 'CIViC webpage hyperlink'"
      :vertical="true"
      input-class="w-full"
      :errors="errors.vci_url"
    />
    <button-row submit-text="save" @submit="save" @cancel="cancel" />
  </div>
</template>
