<script setup>
import { ref, computed, watch } from 'vue'
import { useStore } from 'vuex'
import { api, isValidationError } from '@/http'

const store = useStore()

const group = computed(() => store.getters['groups/currentItemOrNew'])

const caption = ref('')
const errors = ref({})
const saving = ref(false)
const iconInput = ref(null)

watch(group, (g) => {
  	caption.value = g?.caption ?? ''
}, { immediate: true })

async function save(e) {
	e?.preventDefault?.()
	if (!group.value?.uuid || saving.value) return

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

		await api.post(`/api/groups/${group.value.uuid}/caption-icon`, data , {
			headers: { 'Content-Type': 'multipart/form-data' },
		})
		store.commit('pushSuccess', 'Caption/Icon updated.')
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
</script>

<template>
	<form-container>
		<input-row label="Caption (max 250 chars)" :errors="errors.caption">
			<textarea
				v-model="caption"
				maxlength="250"
				rows="3"
				class="w-full"
				placeholder="Short description (plain text only)"
			/>
			<small>{{ (caption?.length || 0) }}/250</small>
		</input-row>

		<input-row label="Icon" :errors="errors.icon">
			<input ref="iconInput" type="file" accept="image/png" />
			<small class="block text-gray-600">Prefer PNG with a transparent background (recommended 600×600). Max 3 MB. Allowed: PNG, JPG/JPEG, GIF.</small>

			<div v-if="group?.icon_url" class="mt-2">
				<img :src="group.icon_url" alt="Current icon" style="max-width:120px;height:auto;" />
			</div>
		</input-row>

		<button-row>
			<button type="button" class="btn blue" :disabled="saving || !group?.uuid" @click="save">
				<span v-if="saving">Saving…</span>
				<span v-else>Save</span>
			</button>
		</button-row>
	</form-container>
</template>
