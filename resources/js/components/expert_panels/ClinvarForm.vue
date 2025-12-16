<script setup>
import { ref, watch } from 'vue'
import { useStore } from 'vuex'
import { api, isValidationError } from '@/http'
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import { hasRole } from "@/auth_utils";
const store = useStore()
const emit = defineEmits(['saved'])
const props = defineProps({ group: { type: Object, required: true } })
const clinvarID = ref('')
const editing = ref(false)
const errors = ref({})
const saving = ref(false)
watch(() => props.group, g => { clinvarID.value = g?.expert_panel?.clinvar_org_id ?? '' }, { immediate: true })
function toggleEditing() { editing.value = !editing.value }
async function save(e) {
	e?.preventDefault?.()
	if (!props.group?.uuid || saving.value) return
	errors.value = {}
	saving.value = true
	try {		
		await api.put(`/api/applications/${props.group.expert_panel.uuid}/clinvar`, {clinvar_org_id: clinvarID.value})
		emit('saved')
		store.commit('pushSuccess', 'ClinVar ID updated.')
		editing.value = false
	} catch (err) {
		if (isValidationError(err)) {
			errors.value = err.response?.data?.errors || {}
		} else {
			store.commit('pushError', err?.response?.data?.message || 'Failed to update ClinVarID.')
		}
	} finally {
		saving.value = false
	}
}
function cancelEdit(e) {
	e?.preventDefault?.()
	editing.value = false
	errors.value = {}
	clinvarID.value = props.group?.expert_panel?.clinvar_org_id ?? ''
}
</script>
<template>
	<header class="flex justify-between items-center">
		<h4>ClinVar Organization ID</h4>
		<EditIconButton v-if="(hasRole('super-admin') || hasRole('coordinator', group) || hasRole('super-user')) && ! editing" @click="toggleEditing" />
    </header>
	<p class="mt-1 text-sm text-gray-600 max-w-2xl">
    	The ClinVar Organization ID is assigned when a VCEP registers as a ClinVar submitter once they receive Step 4 approval. The ClinVar Organization ID will be used to create a link to the VCEP’s ClinVar submitter page.
  	</p>
	<form-container>
		<input-row label="ClinVar Organization ID" 
			:errors="errors.clinvar_id"
			v-model="clinvarID"
			:disabled="!editing"
			placeholder="ClinVar ID"
			:max-length="15"
			label-width-class="w-48"
  			input-class="w-32"
			>
			<template #after-input>
				<span v-if="!editing && !clinvarID" class="text-gray-500 italic">Not set</span>
			</template>
		</input-row>
		<button-row v-if="editing">
			<button type="button" class="btn white" @click="cancelEdit">Cancel</button>
			<button type="button" class="btn blue" :disabled="saving || !group?.uuid" @click="save">
				<span v-if="saving">Saving...</span>
				<span v-else>Save</span>
			</button>
		</button-row>
	</form-container>
</template>