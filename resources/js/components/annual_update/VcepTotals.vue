<script>
import mirror from '@/composables/setup_working_mirror'
import {clone, cloneDeep, debounce, isEqual} from 'lodash-es'

const defaultGene = {
    gene_symbol: null,
    in_clinvar: null,
    gci_approved: null,
    provisionaly_approved: null
}

export default {
    name: 'VcepTotals',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits ],
    setup (props, context) {
        const { workingCopy } = mirror.setup(props, context);
        return {
            workingCopy
        };
    },
    data () {
        return {
            variantCounts: []
        }
    },
    computed: {
        lastYear () {
            return (new Date()).getFullYear() -1;
        },
        isComplete () {
            return Boolean(this.modelValue.completed_at)
        }
    },
    watch: {
        modelValue: {
            deep: true,
            immediate: true,
            handler (to) {
                if (!isEqual(to.data.variant_counts, this.variantCounts)) {
                    this.variantCounts = cloneDeep(to.data.variant_counts)
                }
            }
        },
        variantCounts: {
            deep: true,
            handler (to) {
                if (!isEqual(to, this.modelValue.variant_counts)) {
                    this.debounceSyncModel();
                }
            }
        }
    },
    created () {
        this.debounceSyncModel = debounce(() => {
            this.workingCopy.data.variant_counts = cloneDeep(this.variantCounts);
            this.$emit('update:modelValue', this.workingCopy);
        }, 1500);
    },
    methods: {
        addGene () {
            if (!this.workingCopy.data.variant_counts) {
                this.workingCopy.data.variant_counts = [];
            }
            this.workingCopy.data.variant_counts.push(clone(defaultGene));
            this.newGene = {};
        },
        removeGeneAtIndex (idx) {
            this.variantCounts.splice(idx, 1);
        },
        hasErrorFor (key) {
            return this.errors[key] && this.errors[key].length > 0;
        }
    },
}
</script>
<template>
  <input-row label="" :errors="errors.variant_counts" vertical>
    <table>
      <thead>
        <tr>
          <th>Gene</th>
          <th>In ClinVar</th>
          <th>Approved in VCI<br><small>(Not in ClinVar)</small></th>
          <th>Provisionally approved</th>
          <th v-if="!isComplete" />
        </tr>
      </thead>
      <tbody>
        <tr v-for="(variant, idx) in variantCounts" :key="variant.gene">
          <td>
            <input
              v-model="variant.gene_symbol"
              :disabled="isComplete"
              type="text"
              placeholder="HGNC gene symbol"
              :class="{'border-red-800': hasErrorFor(`variant_counts.${idx}.gene_symbol`)}"
            >
            <input-errors :errors="errors[`variant_counts.${idx}.gene_symbol`] || []" />
          </td>
          <td>
            <input
              v-model="variant.in_clinvar"
              :disabled="isComplete"
              type="number"
              :class="{'border-red-800': hasErrorFor(`variant_counts.${idx}.in_clinvar`)}"
            >
            <input-errors :errors="errors[`variant_counts.${idx}.in_clinvar`] || []" />
          </td>
          <td>
            <input
              v-model="variant.gci_approved"
              :disabled="isComplete"
              type="number"
              :class="{'border-red-800': hasErrorFor(`variant_counts.${idx}.gci_approved`)}"
            >
            <input-errors :errors="errors[`variant_counts.${idx}.gci_approved`] || []" />
          </td>
          <td>
            <input
              v-model="variant.provisionally_approved"
              :disabled="isComplete"
              type="number"
              :class="{'border-red-800': hasErrorFor(`variant_counts.${idx}.provisionally_approved`)}"
            >
            <input-errors :errors="errors[`variant_counts.${idx}.provisionally_approved`] || []" />
          </td>
          <th v-if="!isComplete">
            <trash-icon-button @click="removeGeneAtIndex(idx)" />
          </th>
        </tr>
      </tbody>
    </table>
    <button v-if="!isComplete" class="btn btn-xs" @click="addGene">
      AddGene
    </button>
  </input-row>
</template>
