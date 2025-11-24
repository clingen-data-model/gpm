<script setup>
import { ref, watch } from 'vue'
import { useStore } from 'vuex'
import { api, isValidationError } from '@/http'
import EditIconButton from '@/components/buttons/EditIconButton.vue'

const store = useStore()

const emit = defineEmits(['saved'])
const props = defineProps({ group: { type: Object, required: true } })

const clinvarID = ref('')
const editing = ref(false)
const errors = ref({})
const saving = ref(false)

watch(() => props.group, g => { clinvarID.value = g?.clinvar_id ?? '' }, { immediate: true })

function toggleEditing() { editing.value = !editing.value }

async function save(e) {
	e?.preventDefault?.()
	if (!props.group?.uuid || saving.value) return

	errors.value = {}
	saving.value = true

	try {		
		await api.put(`/api/groups/${props.group.uuid}/clinvar`, {clinvar_id: clinvarID.value})
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
	clinvarID.value = props.group?.clinvar_id ?? ''
}
</script>

<template>
	<header class="flex justify-between items-center">
		<h4>ClinVar ID</h4>
		<EditIconButton v-if="(hasRole('super-admin') || hasRole('coordinator') || hasRole('super-user')) && ! editing" @click="toggleEditing" />
    </header>
	<form-container>
		<input-row label="ClinVar ID" 
			:errors="errors.clinvar_id"
			v-model="clinvarID"
			:disabled="!editing"
			placeholder="ClinVar ID"
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
