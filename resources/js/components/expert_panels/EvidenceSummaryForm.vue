<script>
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error';


export default {
    name: 'EvidenceSummaryForm',
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
            summaryClone: {gene: {}},
            errors: {}
        }
    },
    computed: {

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
                const newSummary = await api({method, url, data: this.summaryClone})
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
    <div class="flex space-x-2">
      <input-row class="mt-0 mb-0" :vertical="true" label="Gene" :errors="errors.gene_id">
        <select v-model="summaryClone.gene_id">
          <option :value="null">
            Select...
          </option>
          <option
            v-for="gene in groupGenes" 
            :key="gene.id"
            :value="gene.id"
          >
            {{ gene.gene_symbol }}
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
    <input-row v-if="group.is_vcep"
      v-model="summaryClone.vci_url" 
      class="mt-0" 
      label="VCI URL" 
      :vertical="true" 
      input-class="w-full" 
      :errors="errors.vci_url"
    />
    <input-row v-if="group.is_scvcep"
      v-model="summaryClone.vci_url" 
      class="mt-0" 
      label="CIViC webpage hyperlink" 
      :vertical="true" 
      :required="true"
      :max-length="10"
      input-class="w-full" 
      :errors="errors.vci_url"
    />
    <button-row submit-text="save" @submit="save" @cancel="cancel" />
  </div>
</template>