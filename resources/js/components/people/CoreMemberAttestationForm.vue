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
const form = ref({ experience_types: [], other_text: null})
const canToggle  = computed(() => ! props.editing)
const localEditing = ref(props.editing)
const showCancel = ! props.editing

onMounted(load)

async function load() {
    loading.value = true
    errors.value = {}
    try {
        const { data } = await api.get(`/api/people/${props.personUuid}/attestation`, {
            headers: { 'X-Ignore-Missing': '1' }
        })
        if (data) {
            form.value.experience_types = (data.experience_types ? data.experience_types : [])
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
            experience_types: form.value.experience_types,
            other_text: form.value.experience_types.includes('other') ? form.value.other_text : null,
        })
        localEditing.value = false
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
            <EditIconButton v-if="canToggle && !localEditing" @click="localEditing = true" />
        </div>
        <div v-if="loading">Loading...</div>
        <form v-else @submit.prevent="submit" class="space-y-4">
            <fieldset class="space-y-2">
                <legend class="font-medium">Core Approval Member Experience (Please check all that apply)</legend>

                <label class="flex items-start gap-2">
                    <input :disabled="! localEditing" type="checkbox" v-model="form.experience_types" value="direct_experience">
                    <span>Direct experience with collection/review of evidence and application of ACMG/AMP criteria</span>
                </label>
                <label class="flex items-start gap-2">
                    <input :disabled="! localEditing" type="checkbox" v-model="form.experience_types" value="detailed_review">
                    <span>Detailed review of collection/review of evidence and application of ACMG/AMP criteria</span>
                </label>
                <label class="flex items-start gap-2">
                    <input :disabled="! localEditing" type="checkbox" v-model="form.experience_types" value="fifty_variants_supervised">
                    <span>At least 50 variants and 1 year of curation experience with supervision or peer review</span>
                </label>
                <label class="flex items-start gap-2">
                    <input :disabled="! localEditing" type="checkbox" v-model="form.experience_types" value="other">
                    <span>Other (describe)</span>
                </label>

                <textarea :disabled="! localEditing" v-if="form.experience_types.includes('other')" class="w-full border rounded p-2" rows="3" v-model="form.other_text" placeholder="Describe your experience" />                
                <div v-if="errors.other_text" class="text-red-600 text-sm">{{ errors.other_text[0] }}</div>
            </fieldset>

            <div v-if="errors.experience_types" class="text-red-600 text-sm">{{ Array.isArray(errors.experience_types) ? errors.experience_types[0] : errors.experience_types }}</div>
            <div v-if="errors['experience_types.*']" class="text-red-600 text-sm">{{ errors['experience_types.*'][0] }}</div>

            <div v-if="localEditing" class="flex items-center gap-2">
                <button v-if="showCancel" type="button" class="rounded border px-3 py-2" @click="localEditing = false">Cancel</button>
                <button class="px-4 py-2 rounded bg-blue-600 text-white">{{ saving ? 'Saving...' : 'Submit Attestation' }}</button>
            </div>
        </form>
    </div>
</template>
