<script setup>
import { ref, computed, watch } from 'vue'
import { useStore } from 'vuex'
import { api, isValidationError } from '@/http'
import EditIconButton from '@/components/buttons/EditIconButton.vue'

const store = useStore()

const emit = defineEmits(['saved'])
const props = defineProps({ group: { type: Object, required: true } })

const caption = ref('')
const editing = ref(false)
const errors = ref({})
const saving = ref(false)
const iconInput = ref(null)

watch(() => props.group, g => { caption.value = g?.caption ?? '' }, { immediate: true })

function toggleEditing() {
  editing.value = !editing.value
}

async function save(e) {
	e?.preventDefault?.()
	if (!props.group?.uuid || saving.value) return

	errors.value = {}
	saving.value = true

	try {
		const data = new FormData()

		if (caption.value !== undefined && caption.value !== null) {
			data.append('caption', caption.value)
		}
		const file = iconInput.value?.files?.[0] || null
		if (file) {
			data.set('icon', file, file.name || 'icon.png')
		}
		data.append('_method', 'PUT')

		await api.post(`/api/groups/${props.group.uuid}/caption-icon`, data , {
			headers: { 'Content-Type': 'multipart/form-data' },
		})
		emit('saved')
		store.commit('pushSuccess', 'Caption/Icon updated.')
		editing.value = false
		if (iconInput.value) iconInput.value.value = null
	} catch (err) {
		if (isValidationError(err)) {
			errors.value = err.response?.data?.errors || {}
		} else {
			store.commit('pushError', err?.response?.data?.message || 'Failed to update branding.')
		}
	} finally {
		saving.value = false
	}
}

function cancelEdit(e) {
	e?.preventDefault?.()
	editing.value = false
	errors.value = {}
	if (iconInput.value) iconInput.value.value = null
	caption.value = props.group?.caption ?? ''
}
</script>

<template>
	<header class="flex justify-between items-center">
      <h4>Working Group Caption and Icon </h4>
      <EditIconButton v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && ! editing" @click="toggleEditing" />
    </header>
	<form-container>
		<input-row label="Caption" :errors="errors.caption">
			<template v-if="! editing">{{ caption }}</template>
			<textarea v-else
				v-model="caption"
				maxlength="250"
				rows="3"
				class="w-full"
				placeholder="Short description (plain text only)"
			/>
			<small v-if="editing">Max. 250 chars. {{ (caption?.length || 0) }}/250.</small>
		</input-row>

		<input-row label="Icon" :errors="errors.icon">
			<template v-if="! editing"></template>
			<template v-else>
				<input ref="iconInput" type="file" accept="image/png" />
				<small class="block text-gray-600">Prefer PNG with a transparent background (recommended 600×600). Max 3 MB. Allowed: PNG, JPG/JPEG, GIF.</small>
			</template>

			<div v-if="group?.icon_url" class="mt-2">
				<img :src="group.icon_url" alt="Current icon" style="max-width:120px;height:auto;" />
			</div>
			<div v-else class="text-gray-600 italic">No icon uploaded yet.</div>
		</input-row>

		<button-row v-if="editing">
			<button type="button" class="btn white" @click="cancelEdit">Cancel</button>
			<button type="button" class="btn blue" :disabled="saving || !group?.uuid" @click="save">
				<span v-if="saving">Saving…</span>
				<span v-else>Save</span>
			</button>
		</button-row>
	</form-container>
</template>
