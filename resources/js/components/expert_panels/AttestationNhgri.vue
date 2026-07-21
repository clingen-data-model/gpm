<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

defineProps({
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update'])
const store = useStore()
const group = computed({
  get() {
    return store.getters['groups/currentItemOrNew']
  },
  set(value) {
    store.commit('groups/addItem', value)
  }
})

const attestation = computed({
  get() {
    return Boolean(group.value?.expert_panel?.nhgri_attestation_date)
  },
  set(value) {
    if (!group.value?.expert_panel) {
      return
    }

    group.value.expert_panel.nhgri_attestation_date = value
      ? new Date().toISOString()
      : null

    emit('update')
  }
})

const checkboxLabel = computed(() => {
  if (group.value?.is_vcep) {
    return 'I understand that once a variant is approved in the VCI it will become publicly available in the Evidence Repository. They should not be held for publication.'
  }

  if (group.value?.is_scvcep) {
    return 'Please check box to confirm your understanding that once a variant classification is approved in CIViC, the group should "publish" the record to make the curation publicly available on CIViC (https://civicdb.org/)'
  }

  return 'Please check box to confirm your understanding that once a gene is approved in the GCI, the group should utilize the “publish” functionality within the GCI to make the curation publicly available on the ClinGen website (https://clinicalgenome.org/). They should not be held for publication.'
})
</script>
<template>
  <div>
    <p v-if="group.is_vcep">
      Curated variants and genes are expected to be approved and posted for the community as soon as possible as described in Section 2.4 of the <vcep-protocol-link />.
      Note that upon approval, a VCEP must finalize their set of variants for upload to the ClinGen Evidence Repository within 30 days.
    </p>
    <p v-if="group.is_scvcep">
      Curated variants and genes are expected to be published to CIViC in a timely manner, as described in the <scvcep-protocol-link />.
      Note that upon approval, an SC-VCEP must work to submit all approved SC-VCEP pilot Assertions to CIViC and/or ClinVar within 30 days.
    </p>
    <p v-if="group.is_gcep">
      Curated genes and variants are expected to be approved and posted for the community as soon as possible and should not wait for the publication of a manuscript.
    </p>

    <p class="my-4">
      <input-row :hide-label="true" :vertical="true">
        <checkbox
          id="nhgri-checkbox"
          v-model="attestation"
          :disabled="disabled"
          :label="checkboxLabel"
        />
      </input-row>
    </p>

    <p v-if="group.is_gcep">
      I understand that once a variant is approved in CIViC it will become publicly available in CIViC. They should not be held for publication.
    </p>
    <p v-if="group.is_vcep">
      Please review the
      <publication-policy-link />
      and refer to guidance on submissions to a preprint server (e.g. bioRxiv or medRxiv).
    </p>
    <p v-if="group.is_gcep">
      <em>It is expected that, whenever possible, Expert Panel manuscripts will be pre-published (e.g. medRXiv) . If the authors do not anticipate submitting their manuscript to a prepublication resource they must provide a written justification.</em>
    </p>
  </div>
</template>
