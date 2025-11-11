<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/http'
import { useStore } from 'vuex'
import EditIconButton from '@/components/buttons/EditIconButton.vue'

const props = defineProps({
    personUuid: { type: String, required: true },
    editing: { type: Boolean, default: true },
    afterSubmit: { type: Function, default: null },
})

const store = useStore()
const loading = ref(true)
const saving = ref(false)
const errors = ref({})
const form = ref({ experience_type: null, other_text: null})
const canToggle  = computed(() => ! props.editing)

onMounted(load)

async function load() {
    loading.value = true
    errors.value = {}
    try {
        const { data } = await api.get(`/api/people/${props.personUuid}/attestation`, {
            headers: { 'X-Ignore-Missing': '1' }
        })
        if (data) {
            form.value.experience_type = data.experience_type ?? null
            form.value.other_text = data.other_text ?? null
            form.value.attestation_version = data.attestation_version ?? null
        }
    } catch (_) {}
    finally { loading.value = false }
}

async function submit() {
    saving.value = true
    errors.value = {}
    try {
        await api.put(`/api/people/${props.personUuid}/attestation`, {
            experience_type: form.value.experience_type,
            other_text: form.value.experience_type === 'other' ? form.value.other_text : null,
        })
        props.editing = false
        await store.dispatch('forceGetCurrentUser')
        props.afterSubmit?.()
    } catch (e) {
        if (e?.response?.data?.errors) errors.value = e.response.data.errors
    } finally {
        saving.value = false
    }
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Core Approval Member Attestation</h3>
            <EditIconButton v-if="canToggle && !editing" @click="editing = true" />
        </div>
        <div v-if="loading">Loading...</div>
        <form v-else @submit.prevent="submit" class="space-y-4">
            <fieldset class="space-y-2">
                <legend class="font-medium">Core Approval Member Experience</legend>

                <label class="flex items-start gap-2">
                    <input :disabled="! editing" type="radio" v-model="form.experience_type" value="direct_experience">
                    <span>Direct experience with collection/review of evidence and application of ACMG/AMP criteria</span>
                </label>
                <label class="flex items-start gap-2">
                    <input :disabled="! editing" type="radio" v-model="form.experience_type" value="detailed_review">
                    <span>Detailed review of collection/review of evidence and application of ACMG/AMP criteria</span>
                </label>
                <label class="flex items-start gap-2">
                    <input :disabled="! editing" type="radio" v-model="form.experience_type" value="fifty_variants_supervised">
                    <span>At least 50 variants and 1 year of curation experience with supervision or peer review</span>
                </label>
                <label class="flex items-start gap-2">
                    <input :disabled="! editing" type="radio" v-model="form.experience_type" value="other">
                    <span>Other (describe)</span>
                </label>

                <textarea :disabled="! editing" v-if="form.experience_type === 'other'" class="w-full border rounded p-2" rows="3" v-model="form.other_text" placeholder="Describe your experience" />
                <div v-if="errors.other_text" class="text-red-600 text-sm">{{ errors.other_text[0] }}</div>
            </fieldset>

            <div v-if="errors.experience_type" class="text-red-600 text-sm">{{ errors.experience_type[0] }}</div>

            <div v-if="editing" class="flex items-center gap-2">
                <button type="button" class="rounded border px-3 py-2" @click="editing = false">Cancel</button>
                <button class="px-4 py-2 rounded bg-blue-600 text-white">{{ saving ? 'Saving...' : 'Submit Attestation' }}</button>
            </div>
        </form>
    </div>
</template>
