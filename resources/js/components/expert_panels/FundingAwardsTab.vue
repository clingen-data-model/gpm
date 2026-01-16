<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import api from '@/http/api'
import SubmissionWrapper from '@/components/groups/SubmissionWrapper.vue'
import { hasPermission } from '@/auth_utils'

const props = defineProps({
  expertPanel: { type: Object, required: true },
})

const loading = ref(false)
const awards = ref([])

const fundingSourcesLoading = ref(false)
const fundingSources = ref([])

const piOptionsLoading = ref(false)
const piOptions = ref([])

const showForm = ref(false)
const editing = ref(null)
const errors = ref({})

const canManage = computed(() => hasPermission('ep-applications-manage'))

const selectedPiIds = ref([])
const primaryPiId = ref(null)

const piNameById = computed(() => {
  const map = new Map()
  for (const p of piOptions.value) map.set(p.id, p.name)
  return map
})

const form = reactive({
  funding_source_id: '',
  award_number: '',
  start_date: '',
  end_date: '',
  nih_reporter_url: '',
  nih_ic: '',
  contact_1_role: '',
  contact_1_name: '',
  contact_1_email: '',
  contact_1_phone: '',
  contact_2_role: '',
  contact_2_name: '',
  contact_2_email: '',
  contact_2_phone: '',
  notes: '',
})

const sort = ref({ field: 'id', desc: true })
const fields = [
  { name: 'id', label: 'ID', sortable: true },
  { name: 'fundingSource', label: 'Funding Source', sortable: false },
  { name: 'award_number', label: 'Award #', sortable: false },
  { name: 'dates', label: 'Dates', sortable: false },
  { name: 'contactPis', label: 'Contact PI(s)', sortable: false },
  { name: 'nih', label: 'NIH', sortable: false },
  { name: 'actions', label: '', sortable: false },
]

function resetForm() {
  form.funding_source_id = ''
  form.award_number = ''
  form.start_date = ''
  form.end_date = ''
  form.nih_reporter_url = ''
  form.nih_ic = ''
  form.contact_1_role = ''
  form.contact_1_name = ''
  form.contact_1_email = ''
  form.contact_1_phone = ''
  form.contact_2_role = ''
  form.contact_2_name = ''
  form.contact_2_email = ''
  form.contact_2_phone = ''
  form.notes = ''
  errors.value = {}

  selectedPiIds.value = []
  primaryPiId.value = null
}

function startCreate() {
  editing.value = null
  resetForm()
  showForm.value = true
}

function startEdit(item) {
  editing.value = item
  errors.value = {}

  // support either naming style from backend
  form.funding_source_id = String(
    item.funding_source_id ?? item.fundingSource?.id ?? item.funding_source?.id ?? ''
  )

  form.award_number = item.award_number ?? ''
  form.start_date = item.start_date ?? ''
  form.end_date = item.end_date ?? ''
  form.nih_reporter_url = item.nih_reporter_url ?? ''
  form.nih_ic = item.nih_ic ?? ''

  form.contact_1_role = item.contact_1_role ?? ''
  form.contact_1_name = item.contact_1_name ?? ''
  form.contact_1_email = item.contact_1_email ?? ''
  form.contact_1_phone = item.contact_1_phone ?? ''

  form.contact_2_role = item.contact_2_role ?? ''
  form.contact_2_name = item.contact_2_name ?? ''
  form.contact_2_email = item.contact_2_email ?? ''
  form.contact_2_phone = item.contact_2_phone ?? ''

  form.notes = item.notes ?? ''

  const pis = item.contactPis || item.contact_pis || []
  selectedPiIds.value = pis.map(p => Number(p.id)).filter(Boolean)

  const primary = pis.find(p => p?.pivot?.is_primary)
  primaryPiId.value = primary ? Number(primary.id) : (selectedPiIds.value[0] ?? null)

  showForm.value = true
}

watch(selectedPiIds, (ids) => {
  if (!ids || ids.length === 0) {
    primaryPiId.value = null
    return
  }
  if (primaryPiId.value == null || !ids.includes(primaryPiId.value)) {
    primaryPiId.value = ids[0]
  }
})

async function fetchAwards() {
  loading.value = true
  try {
    const res = await api.get(`/api/applications/${props.expertPanel.uuid}/funding-awards`)
    awards.value = Array.isArray(res.data) ? res.data : []
  } finally {
    loading.value = false
  }
}

async function fetchFundingSources() {
  fundingSourcesLoading.value = true
  try {
    const res = await api.get('/api/funding-sources')
    fundingSources.value = Array.isArray(res.data) ? res.data : []
  } finally {
    fundingSourcesLoading.value = false
  }
}

async function fetchPiOptions() {
  piOptionsLoading.value = true
  try {
    const res = await api.get(`/api/applications/${props.expertPanel.uuid}/funding-awards/pi-options`)
    piOptions.value = Array.isArray(res.data) ? res.data : []
  } finally {
    piOptionsLoading.value = false
  }
}

function fundingSourceName(row) {
  return row?.fundingSource?.name || row?.funding_source?.name || '—'
}

function fundingTypeName(row) {
  return (
    row?.fundingSource?.fundingType?.name ||
    row?.funding_source?.funding_type?.name ||
    row?.fundingSource?.type ||
    row?.funding_source?.type ||
    ''
  )
}

function fmtDates(row) {
  const s = row?.start_date || '—'
  const e = row?.end_date || 'Present'
  return `${s} — ${e}`
}

function firstError(field) {
  const e = errors.value?.[field]
  return Array.isArray(e) ? e[0] : ''
}

function stripHtml(text) {
  return (text ?? '').replace(/<[^>]*>/g, '')
}

function sanitizeNotes(text) {
  // notes column is TEXT; keep text-only to avoid HTML
  return stripHtml(text).trim()
}

function pisDisplay(row) {
  const pis = row?.contactPis || row?.contact_pis || []
  if (!pis.length) return '—'
  const names = pis.map(p => p?.name || p?.full_name).filter(Boolean)
  const primary = pis.find(p => p?.pivot?.is_primary)
  if (primary?.name || primary?.full_name) {
    const primaryName = primary.name || primary.full_name
    return `${names.join(', ')} (Primary: ${primaryName})`
  }
  return names.join(', ')
}

async function save() {
  errors.value = {}

  const payload = {
    funding_source_id: form.funding_source_id ? Number(form.funding_source_id) : null,
    award_number: form.award_number || null,
    start_date: form.start_date || null,
    end_date: form.end_date || null,
    nih_reporter_url: form.nih_reporter_url || null,
    nih_ic: form.nih_ic || null,

    contact_1_role: form.contact_1_role || null,
    contact_1_name: form.contact_1_name || null,
    contact_1_email: form.contact_1_email || null,
    contact_1_phone: form.contact_1_phone || null,

    contact_2_role: form.contact_2_role || null,
    contact_2_name: form.contact_2_name || null,
    contact_2_email: form.contact_2_email || null,
    contact_2_phone: form.contact_2_phone || null,

    notes: sanitizeNotes(form.notes) || null,

    contact_pi_person_ids: selectedPiIds.value.map(Number),
    primary_contact_pi_id: primaryPiId.value ? Number(primaryPiId.value) : null,
  }

  try {
    if (editing.value) {
      await api.put(
        `/api/applications/${props.expertPanel.uuid}/funding-awards/${editing.value.id}`,
        payload
      )
    } else {
      await api.post(
        `/api/applications/${props.expertPanel.uuid}/funding-awards`,
        payload
      )
    }

    showForm.value = false
    await fetchAwards()
  } catch (e) {
    if (e?.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      return
    }
    throw e
  }
}

async function destroyAward(item) {
  if (!confirm('Delete this funding award?')) return
  await api.delete(`/api/applications/${props.expertPanel.uuid}/funding-awards/${item.id}`)
  await fetchAwards()
}

onMounted(async () => {
  await Promise.all([
    fetchFundingSources(),
    fetchPiOptions(),
    fetchAwards(),
  ])
})
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-3">
      <h2 class="m-0">Funding Awards</h2>
      <button v-if="canManage" class="btn btn-xs" type="button" @click="startCreate">
        Add Award
      </button>
    </div>

    <div v-if="loading" class="text-center w-full">Loading...</div>

    <div v-else>
      <data-table
        v-model:sort="sort"
        v-remaining-height
        :data="awards"
        :fields="fields"
        row-class="active:bg-blue-100"
      >
        <template #cell-fundingSource="{ item }">
          <div class="font-medium">
            {{ fundingSourceName(item) }}
          </div>
          <div v-if="fundingTypeName(item)" class="text-xs text-gray-600">
            {{ fundingTypeName(item) }}
          </div>
        </template>

        <template #cell-dates="{ item }">
          <div class="text-sm">
            {{ fmtDates(item) }}
          </div>
        </template>

        <template #cell-contactPis="{ item }">
          <div class="text-sm">
            {{ pisDisplay(item) }}
          </div>
        </template>

        <template #cell-nih="{ item }">
          <div class="text-sm">
            <template v-if="item.nih_reporter_url">
              <a class="link" :href="item.nih_reporter_url" target="_blank" rel="noopener">
                NIH Reporter
              </a>
            </template>
            <template v-else>—</template>
          </div>
          <div v-if="item.nih_ic" class="text-xs text-gray-600">
            IC: {{ item.nih_ic }}
          </div>
        </template>

        <template #cell-actions="{ item }">
          <div v-if="canManage" class="flex items-center gap-2 whitespace-nowrap">
            <button class="btn btn-xs" @click.stop="startEdit(item)">Edit</button>
            <button class="btn btn-xs" @click.stop="destroyAward(item)">Delete</button>
          </div>
        </template>
      </data-table>

      <div v-if="awards.length === 0" class="text-gray-600 mt-2">
        No funding awards added yet.
      </div>
    </div>

    <modal-dialog
      v-model="showForm"
      :title="editing ? 'Edit Funding Award' : 'Add Funding Award'"
      size="lg"
    >
      <SubmissionWrapper @submitted="save" @canceled="showForm = false">
        <div class="space-y-4">
          <div>
            <label class="block text-sm">Funding Source</label>
            <select v-model="form.funding_source_id" class="w-full" :disabled="fundingSourcesLoading">
              <option value="" disabled>
                {{ fundingSourcesLoading ? 'Loading…' : 'Select a funding source…' }}
              </option>
              <option v-for="s in fundingSources" :key="s.id" :value="String(s.id)">
                {{ s.name }}
                <template v-if="s.funding_type?.name || s.fundingType?.name">
                  — {{ s.funding_type?.name || s.fundingType?.name }}
                </template>
              </option>
            </select>
            <div v-if="firstError('funding_source_id')" class="text-sm text-red-600 mt-1">
              {{ firstError('funding_source_id') }}
            </div>
          </div>

          <!-- Contact PIs -->
          <div class="border rounded p-3">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-sm font-semibold m-0">Contact PI(s)</h3>
              <div v-if="piOptionsLoading" class="text-xs text-gray-600">Loading…</div>
            </div>

            <div v-if="!piOptionsLoading && piOptions.length === 0" class="text-sm text-gray-600">
              No active group members available.
            </div>

            <div v-else class="space-y-2">
              <div class="text-xs text-gray-600">
                Select PI(s) from active group members. Choose a Primary PI.
              </div>

              <div class="max-h-40 overflow-auto border rounded p-2 space-y-1">
                <label
                  v-for="p in piOptions"
                  :key="p.id"
                  class="flex items-center gap-2 text-sm"
                >
                  <input
                    type="checkbox"
                    :value="Number(p.id)"
                    v-model="selectedPiIds"
                  />
                  <span class="font-medium">{{ p.name }}</span>
                  <span v-if="p.email" class="text-gray-600">({{ p.email }})</span>
                </label>
              </div>

              <div>
                <label class="block text-sm">Primary PI</label>
                <select v-model="primaryPiId" class="w-full" :disabled="selectedPiIds.length === 0">
                  <option v-if="selectedPiIds.length === 0" :value="null">
                    Select PI(s) first…
                  </option>
                  <option v-for="id in selectedPiIds" :key="id" :value="id">
                    {{ piNameById.get(id) || `Person #${id}` }}
                  </option>
                </select>

                <div v-if="firstError('contact_pi_person_ids')" class="text-sm text-red-600 mt-1">
                  {{ firstError('contact_pi_person_ids') }}
                </div>
                <div v-if="firstError('primary_contact_pi_id')" class="text-sm text-red-600 mt-1">
                  {{ firstError('primary_contact_pi_id') }}
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm">Award Number</label>
              <input v-model="form.award_number" class="w-full" type="text" maxlength="30" placeholder="(optional)">
              <div v-if="firstError('award_number')" class="text-sm text-red-600 mt-1">
                {{ firstError('award_number') }}
              </div>
            </div>

            <div>
              <label class="block text-sm">NIH IC</label>
              <input v-model="form.nih_ic" class="w-full" type="text" maxlength="255" placeholder="(optional)">
              <div v-if="firstError('nih_ic')" class="text-sm text-red-600 mt-1">
                {{ firstError('nih_ic') }}
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm">NIH Reporter URL</label>
            <input v-model="form.nih_reporter_url" class="w-full" type="text" maxlength="255" placeholder="https://reporter.nih.gov/... (optional)">
            <div v-if="firstError('nih_reporter_url')" class="text-sm text-red-600 mt-1">
              {{ firstError('nih_reporter_url') }}
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm">Start Date</label>
              <input v-model="form.start_date" class="w-full" type="date">
              <div v-if="firstError('start_date')" class="text-sm text-red-600 mt-1">
                {{ firstError('start_date') }}
              </div>
            </div>

            <div>
              <label class="block text-sm">End Date</label>
              <input v-model="form.end_date" class="w-full" type="date">
              <div v-if="firstError('end_date')" class="text-sm text-red-600 mt-1">
                {{ firstError('end_date') }}
              </div>
            </div>
          </div>

          <div class="border-t pt-3">
            <h3 class="text-sm font-semibold mb-2">Award Rep. Contact #1</h3>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm">Role</label>
                <input v-model="form.contact_1_role" class="w-full" type="text" maxlength="255" placeholder="(optional)">
              </div>
              <div>
                <label class="block text-sm">Name</label>
                <input v-model="form.contact_1_name" class="w-full" type="text" maxlength="255" placeholder="(optional)">
              </div>
              <div>
                <label class="block text-sm">Email</label>
                <input v-model="form.contact_1_email" class="w-full" type="email" maxlength="255" placeholder="(optional)">
              </div>
              <div>
                <label class="block text-sm">Phone</label>
                <input v-model="form.contact_1_phone" class="w-full" type="text" maxlength="255" placeholder="(optional)">
              </div>
            </div>
          </div>

          <div class="border-t pt-3">
            <h3 class="text-sm font-semibold mb-2">Award Rep. Contact #2</h3>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-sm">Role</label>
                <input v-model="form.contact_2_role" class="w-full" type="text" maxlength="255" placeholder="(optional)">
                <div v-if="firstError('contact_2_role')" class="text-sm text-red-600 mt-1">
                  {{ firstError('contact_2_role') }}
                </div>
              </div>
              <div>
                <label class="block text-sm">Name</label>
                <input v-model="form.contact_2_name" class="w-full" type="text" maxlength="255" placeholder="(optional)">
                <div v-if="firstError('contact_2_name')" class="text-sm text-red-600 mt-1">
                  {{ firstError('contact_2_name') }}
                </div>
              </div>
              <div>
                <label class="block text-sm">Email</label>
                <input v-model="form.contact_2_email" class="w-full" type="email" maxlength="255" placeholder="(optional)">
                <div v-if="firstError('contact_2_email')" class="text-sm text-red-600 mt-1">
                  {{ firstError('contact_2_email') }}
                </div>
              </div>
              <div>
                <label class="block text-sm">Phone</label>
                <input v-model="form.contact_2_phone" class="w-full" type="text" maxlength="255" placeholder="(optional)">
                <div v-if="firstError('contact_2_phone')" class="text-sm text-red-600 mt-1">
                  {{ firstError('contact_2_phone') }}
                </div>
              </div>
            </div>
          </div>

          <div class="border-t pt-3">
            <label class="block text-sm font-semibold">Notes</label>
            <textarea
              v-model="form.notes"
              class="w-full"
              rows="4"
              placeholder="(optional)"
            />
            <div class="text-xs text-gray-600 mt-1">
              Text-only. HTML will be stripped.
            </div>
            <div v-if="firstError('notes')" class="text-sm text-red-600 mt-1">
              {{ firstError('notes') }}
            </div>
          </div>
        </div>
      </SubmissionWrapper>
    </modal-dialog>
  </div>
</template>
