<script setup>
import { ref, reactive, computed, watch } from 'vue'
import api from '@/http/api'
import SubmissionWrapper from '@/components/groups/SubmissionWrapper.vue'
import { hasRole, hasPermission } from '@/auth_utils'
import SearchSelect from '@/components/forms/SearchSelect.vue'

const props = defineProps({
  expertPanel: { type: Object, required: true },
})

const MAX_REP_CONTACTS = 3

const expertPanelUuid = computed(() => props.expertPanel?.uuid || null)

const loading = ref(false)
const awards = ref([])

const fundingSourcesLoading = ref(false)
const fundingSources = ref([])

const showForm = ref(false)
const editing = ref(null)
const errors = ref({})

const canManage = computed(() => hasRole('super-user') || hasRole('super-admin') || hasPermission('funding-sources-manage'))

const selectedPis = ref([])
const primaryPiId = ref(null)

const deletingAgreementAward = ref(null)

function emptyRepContact() {
  return {
    role: '',
    name: '',
    email: '',
    phone: '',
  }
}

function addRepContact() {
  if (form.rep_contacts.length >= MAX_REP_CONTACTS) return
  form.rep_contacts.push(emptyRepContact())
}

function removeRepContact(index) {
  form.rep_contacts.splice(index, 1)

  if (!form.rep_contacts.length) {
    form.rep_contacts.push(emptyRepContact())
  }
}

function firstRepContactError(index, field) {
  const e = errors.value?.[`rep_contacts.${index}.${field}`]
  return Array.isArray(e) ? e[0] : ''
}

const form = reactive({
  funding_sources: [],
  award_number: '',
  start_date: '',
  end_date: '',
  award_url: '',
  funding_source_division: '',
  rep_contacts: [emptyRepContact()],
  notes: '',
})

const sort = ref({ field: 'id', desc: true })
const baseFields = [
  { name: 'id', label: 'ID', sortable: true },
  { name: 'fundingSources', label: 'Funding Sources', sortable: false },
  { name: 'award_number', label: 'Award #', sortable: false },
  { name: 'dates', label: 'Dates', sortable: false },
  { name: 'contactPis', label: 'Contact PI(s)', sortable: false },
  { name: 'nih', label: 'NIH', sortable: false },
]
const fields = computed(() => {
  return canManage.value
    ? [...baseFields, { name: 'actions', label: '', sortable: false }]
    : baseFields
})

function resetForm() {
  form.funding_sources  = []
  form.award_number = ''
  form.start_date = ''
  form.end_date = ''
  form.award_url = ''
  form.funding_source_division = ''
  form.rep_contacts = [emptyRepContact()]
  form.notes = ''
  errors.value = {}

  selectedPis.value = []
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

  form.funding_sources = [ ...(item.funding_sources || [])]
  form.award_number = item.award_number ?? ''
  form.start_date = item.start_date ?? ''
  form.end_date = item.end_date ?? ''
  form.award_url = item.award_url ?? ''
  form.funding_source_division = item.funding_source_division ?? ''
  form.rep_contacts = Array.isArray(item.rep_contacts) && item.rep_contacts.length
    ? item.rep_contacts.map(contact => ({
        role: contact?.role ?? '',
        name: contact?.name ?? '',
        email: contact?.email ?? '',
        phone: contact?.phone ?? '',
      }))
    : [emptyRepContact()]
  form.notes = item.notes ?? ''
  const pis = item.contact_pis || []
  selectedPis.value = [...pis]
  const primary = pis.find(p => Boolean(p?.pivot?.is_primary))
  primaryPiId.value = primary ? Number(primary.id) : (pis[0]?.id ? Number(pis[0].id) : null)

  showForm.value = true
}

async function fetchAwards() {
  if (!expertPanelUuid.value) return
  loading.value = true
  try {
    const res = await api.get(`/api/applications/${expertPanelUuid.value}/funding-awards`)
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
  return stripHtml(text).trim()
}

function pisParts(row) {
  const pis = row?.contact_pis || []
  if (!pis.length) return { primary: '', others: '' }

  const primary = pis.find(p => Boolean(p?.pivot?.is_primary))
  const primaryName = primary?.name || ''

  const others = pis
    .filter(p => !p?.pivot?.is_primary)
    .filter(p => !primary?.id || p?.id !== primary.id)
    .map(p => p?.name)
    .filter(Boolean)
    .join(', ')

  return { primary: primaryName, others }
}

function primaryLine(row) {
  const { primary } = pisParts(row)
  return primary ? `Primary: ${primary}` : ''
}

function othersLine(row) {
  const { others } = pisParts(row)
  return others
}

async function save() {
  errors.value = {}

  const payload = {
    funding_source_ids: form.funding_sources.map(source => Number(source.id)).filter(Boolean),
    award_number: form.award_number || null,
    start_date: form.start_date || null,
    end_date: form.end_date || null,
    award_url: form.award_url || null,
    funding_source_division: form.funding_source_division || null,
    rep_contacts: (form.rep_contacts || [])
      .map(contact => ({
        role: contact.role || null,
        name: contact.name || null,
        email: contact.email || null,
        phone: contact.phone || null,
      }))
      .filter(contact =>
        Object.values(contact).some(value => value !== null && String(value).trim() !== '')
      ),
    notes: sanitizeNotes(form.notes) || null,
    contact_pi_person_ids: selectedPis.value.map(person => Number(person.id)).filter(Boolean),
    primary_contact_pi_id: primaryPiId.value ? Number(primaryPiId.value) : null,
  }

  try {
    if (editing.value) {
      await api.put(`/api/applications/${expertPanelUuid.value}/funding-awards/${editing.value.id}`, payload)
    } else {
      await api.post(`/api/applications/${expertPanelUuid.value}/funding-awards`, payload)
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
  await api.delete(`/api/applications/${expertPanelUuid.value}/funding-awards/${item.id}`)
  await fetchAwards()
}

/** Upload section */
const showUploadForm = ref(false)
const uploadingAward = ref(null)
const uploadSaving = ref(false)
const uploadErrors = ref({})
const partnershipAgreementFile = ref(null)

function uploadDocument(item) {
  uploadingAward.value = item
  partnershipAgreementFile.value = null
  uploadErrors.value = {}
  showUploadForm.value = true
}

function setPartnershipAgreementFile(event) {
  partnershipAgreementFile.value = event.target.files?.[0] || null
}

function firstUploadError(field) {
  const e = uploadErrors.value?.[field]
  return Array.isArray(e) ? e[0] : ''
}

async function savePartnershipAgreement() {
  if (!uploadingAward.value) return
  if (!partnershipAgreementFile.value) {
    uploadErrors.value = { partnership_agreement: ['Please select a file to upload.'] }
    return
  }

  uploadErrors.value = {}
  uploadSaving.value = true

  const payload = new FormData()
  payload.append('partnership_agreement', partnershipAgreementFile.value)

  try {
    await api.post(`/api/applications/${expertPanelUuid.value}/funding-awards/${uploadingAward.value.id}/agreement`, payload, { headers: { 'Content-Type': 'multipart/form-data' }})
    showUploadForm.value = false
    await fetchAwards()
  } catch (e) {
    if (e?.response?.status === 422) {
      uploadErrors.value = e.response.data.errors || {}
      return
    }
    throw e
  } finally {
    uploadSaving.value = false
  }
}
function partnershipAgreementDownloadUrl(item) {
  return `/api/applications/${expertPanelUuid.value}/funding-awards/${item.id}/agreement`
}

const searchFundingSources = (keyword, options) => {
  const term = (keyword ?? '').trim().toLowerCase()
  if (!term) {
    return options
  }

  return options.filter(source => {
    const name = (source.name ?? '').toLowerCase()
    const type = (source.funding_type?.name ?? '').toLowerCase()
    return name.includes(term) || type.includes(term)
  })
}

async function deleteAgreement(item) {
  if (!item?.partnership_agreement_file) return
  if (!confirm('Remove this partnership agreement file?')) return
  deletingAgreementAward.value = item.id
  try {
    await api.delete(`/api/applications/${expertPanelUuid.value}/funding-awards/${item.id}/agreement`)
    await fetchAwards()
  } finally {
    deletingAgreementAward.value = null
  }
}

async function searchPis(keyword) {
  const q = (keyword ?? '').trim()
  if (q.length < 3) { return []; }
  const res = await api.get(`/api/applications/${expertPanelUuid.value}/funding-awards/pi-options`, {params: { q, limit: 25 }})
  return Array.isArray(res.data) ? res.data : []
}

watch(
  selectedPis,
  (pis) => {
    const ids = (pis || []).map(p => Number(p.id)).filter(Boolean)
    if (!ids.length) {
      primaryPiId.value = null
      return
    }

    if (primaryPiId.value == null || !ids.includes(Number(primaryPiId.value))) {
      primaryPiId.value = ids[0]
    }
  },
  { deep: true }
)

watch(
  expertPanelUuid,
  async (uuid) => {
    if (!uuid) {
      return
    }

    await fetchAwards()

    if (canManage.value) {
      await fetchFundingSources()
    }
  },
  { immediate: true }
)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-3">
      <h2 class="m-0">Funding Awards</h2>
      <button v-if="canManage" class="btn btn-xs" type="button" @click="startCreate">
        Add Award
      </button>
    </div>
    <div v-if="!canManage" class="text-sm text-gray-600 mb-3">
      If your group has funding awards that should be added or updated in GPM, please contact<a class="link" href="mailto:gpm_support@clinicalgenome.org">gpm_support@clinicalgenome.org</a> for assistance.
    </div>

    <div v-if="loading" class="text-center w-full">Loading...</div>

    <div v-else :class="{ 'max-h-[600px] overflow-y-auto': awards.length > 20 }">
      <data-table v-model:sort="sort" :data="awards" :fields="fields" row-class="active:bg-blue-100">
        <template #cell-fundingSources="{ item }">
          <div v-for="source in (item.funding_sources || [])" :key="source.id" class="mb-1 last:mb-0">
            <div class="font-medium">{{ source.name }}</div>
            <div v-if="source.funding_type?.name" class="text-xs text-gray-600">
              Funding Type: {{ source.funding_type?.name }}
            </div>
          </div>
          <div v-if="!item.funding_sources?.length" class="text-gray-500"> — </div>
        </template>

        <template #cell-award_number="{ item }">
          <div class="text-sm">{{ item.award_number || '—' }}</div>
          <div v-if="item.partnership_agreement_file" class="text-xs mt-1 flex items-center gap-1">
            Partnership Agreement: <a class="link" :href="partnershipAgreementDownloadUrl(item)" target="_blank" rel="noopener">{{ item.partnership_agreement_file }}</a>
          </div>
          <div v-if="deletingAgreementAward === item.id" class="text-xs text-gray-600 mt-1">Removing partnership agreement file...</div>
        </template>

        <template #cell-dates="{ item }">
          <div class="text-sm"> {{ fmtDates(item) }} </div>
        </template>

        <template #cell-contactPis="{ item }">
          <div v-if="primaryLine(item)" class="text-sm">
            <span class="font-medium">{{ primaryLine(item) }}</span>
          </div>
          <div v-if="othersLine(item)" class="text-sm text-gray-700">{{ othersLine(item) }}</div>
          <div v-if="!primaryLine(item) && !othersLine(item)" class="text-gray-500">-</div>
        </template>

        <template #cell-nih="{ item }">
          <div class="text-sm">
            <template v-if="item.award_url">
              <a class="link" :href="item.award_url" target="_blank" rel="noopener">
                {{ item.funding_source_division ?? 'NIH Reporter' }}
              </a>
            </template>
            <template v-else>{{ item.funding_source_division ?? 'n.a' }}</template>
          </div>
        </template>

        <template #cell-actions="{item}">
        <dropdown-menu v-if="canManage" hide-cheveron>
          <template #label>
            <button class="btn btn-xs">
              &hellip;
            </button>
          </template>
          <dropdown-item @click="uploadDocument(item)">{{ item.partnership_agreement_file ? 'Replace Partnership Agreement File' : 'Upload Partnership Agreement File' }}</dropdown-item>
          <dropdown-item v-if="item.partnership_agreement_file" @click="deleteAgreement(item)">Remove Partnership Agreement File</dropdown-item>
          <dropdown-item @click="startEdit(item)">Update</dropdown-item>
          <dropdown-item @click="destroyAward(item)">Delete</dropdown-item>
        </dropdown-menu>
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
            <label class="block text-sm">Funding Sources <span class="text-red-500">*</span></label>
            <SearchSelect
              v-model="form.funding_sources"
              :options="fundingSources"
              multiple
              show-options-on-focus
              show-options-when-empty
              :search-function="searchFundingSources"
              :disabled="fundingSourcesLoading"
            />

            <div class="text-xs text-gray-600 mt-1">
              Don't see the funding source you need? <a class="link" href="mailto:partnership@clinicalgenome.org">partnership@clinicalgenome.org</a> 
              or <a class="link" href="mailto:gpm_support@clinicalgenome.org">gpm_support@clinicalgenome.org</a>
            </div>
            <div v-if="firstError('funding_source_ids') || firstError('funding_source_ids.0')" class="text-sm text-red-600 mt-1">
              {{ firstError('funding_source_ids') || firstError('funding_source_ids.0') }}
            </div>
          </div>

          <div class="border rounded p-3">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-sm font-semibold m-0">Contact PI(s)</h3>
            </div>

            <SearchSelect
              v-model="selectedPis"
              :options="[]"
              multiple
              :search-function="searchPis"
              :disabled="!canManage"
              placeholder="Type at least 2 characters to search…"
            >
              <template #selection-label="{ selection }">{{ selection.name }}</template>
              <template #option="{ option }">
                <div>
                  <div class="font-medium">{{ option.name }}</div>
                  <div v-if="option.email" class="text-xs text-gray-600">{{ option.email }}</div>
                </div>
              </template>
            </SearchSelect>

            <div class="mt-3">
              <label class="block text-sm">Primary PI</label>
              <select v-model="primaryPiId" class="w-full" :disabled="selectedPis.length === 0">
                <option v-if="selectedPis.length === 0" :value="null">Select PI(s) first…</option>
                <option v-for="person in selectedPis" :key="person.id" :value="Number(person.id)">{{ person.name }}</option>
              </select>

              <div v-if="firstError('contact_pi_person_ids')" class="text-sm text-red-600 mt-1">
                {{ firstError('contact_pi_person_ids') }}
              </div>
              <div v-if="firstError('primary_contact_pi_id')" class="text-sm text-red-600 mt-1">
                {{ firstError('primary_contact_pi_id') }}
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm">Award Number</label>
              <input
                v-model="form.award_number"
                class="w-full"
                type="text"
                maxlength="30"
                placeholder="(optional)"
              />
              <div v-if="firstError('award_number')" class="text-sm text-red-600 mt-1">
                {{ firstError('award_number') }}
              </div>
            </div>

            <div>
              <label class="block text-sm">NIH IC</label>
              <input
                v-model="form.funding_source_division"
                class="w-full"
                type="text"
                maxlength="255"
                placeholder="(optional)"
              />
              <div v-if="firstError('funding_source_division')" class="text-sm text-red-600 mt-1">
                {{ firstError('funding_source_division') }}
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm">NIH Reporter URL</label>
            <input
              v-model="form.award_url"
              class="w-full"
              type="text"
              maxlength="255"
              placeholder="https://reporter.nih.gov/... (optional)"
            />
            <div v-if="firstError('award_url')" class="text-sm text-red-600 mt-1">
              {{ firstError('award_url') }}
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-sm">Start Date</label>
              <input v-model="form.start_date" class="w-full" type="date" />
              <div v-if="firstError('start_date')" class="text-sm text-red-600 mt-1">
                {{ firstError('start_date') }}
              </div>
            </div>

            <div>
              <label class="block text-sm">End Date</label>
              <input v-model="form.end_date" class="w-full" type="date" />
              <div v-if="firstError('end_date')" class="text-sm text-red-600 mt-1">
                {{ firstError('end_date') }}
              </div>
            </div>
          </div>

          <div class="border-t pt-3">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-sm font-semibold m-0">Award Rep. Contacts</h3>
              <button
                v-if="canManage && form.rep_contacts.length < MAX_REP_CONTACTS"
                class="btn btn-xs"
                type="button"
                @click="addRepContact"
              >
                + Add more
              </button>
            </div>

            <div
              v-for="(contact, index) in form.rep_contacts"
              :key="`rep-contact-${index}`"
              class="border rounded p-3 mb-3 last:mb-0"
            >
              <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-semibold m-0">Award Rep. Contact #{{ index + 1 }}</h4>
                <button
                  v-if="canManage && form.rep_contacts.length > 1"
                  class="btn btn-xs"
                  type="button"
                  @click="removeRepContact(index)"
                >
                  Remove
                </button>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-sm">Role</label>
                  <input
                    v-model="contact.role"
                    class="w-full"
                    type="text"
                    maxlength="255"
                    placeholder="(optional)"
                  />
                  <div v-if="firstRepContactError(index, 'role')" class="text-sm text-red-600 mt-1">
                    {{ firstRepContactError(index, 'role') }}
                  </div>
                </div>

                <div>
                  <label class="block text-sm">Name</label>
                  <input
                    v-model="contact.name"
                    class="w-full"
                    type="text"
                    maxlength="255"
                    placeholder="(optional)"
                  />
                  <div v-if="firstRepContactError(index, 'name')" class="text-sm text-red-600 mt-1">
                    {{ firstRepContactError(index, 'name') }}
                  </div>
                </div>

                <div>
                  <label class="block text-sm">Email</label>
                  <input
                    v-model="contact.email"
                    class="w-full"
                    type="email"
                    maxlength="255"
                    placeholder="(optional)"
                  />
                  <div v-if="firstRepContactError(index, 'email')" class="text-sm text-red-600 mt-1">
                    {{ firstRepContactError(index, 'email') }}
                  </div>
                </div>

                <div>
                  <label class="block text-sm">Phone</label>
                  <input
                    v-model="contact.phone"
                    class="w-full"
                    type="text"
                    maxlength="255"
                    placeholder="(optional)"
                  />
                  <div v-if="firstRepContactError(index, 'phone')" class="text-sm text-red-600 mt-1">
                    {{ firstRepContactError(index, 'phone') }}
                  </div>
                </div>
              </div>
            </div>

            <div v-if="firstError('rep_contacts')" class="text-sm text-red-600 mt-1">
              {{ firstError('rep_contacts') }}
            </div>
          </div>

          <div class="border-t pt-3">
            <label class="block text-sm font-semibold">Notes</label>
            <textarea v-model="form.notes" class="w-full" rows="4" placeholder="(optional)" />
            <div class="text-xs text-gray-600 mt-1">Text-only. HTML will be stripped.</div>
            <div v-if="firstError('notes')" class="text-sm text-red-600 mt-1">
              {{ firstError('notes') }}
            </div>
          </div>
        </div>
      </SubmissionWrapper>
    </modal-dialog>

    <modal-dialog
      v-model="showUploadForm"
      title="Upload Partnership Agreement"
      size="md"
    >
      <SubmissionWrapper
        @submitted="savePartnershipAgreement"
        @canceled="showUploadForm = false"
      >
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-semibold">Partnership Agreement</label>
            <input
              class="w-full"
              type="file"
              accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
              @change="setPartnershipAgreementFile"
            />

            <div class="text-xs text-gray-600 mt-1">Preferred format: PDF. Accepted file types: PDF, DOC, DOCX. Maximum file size: 3 MB.</div>
            <div v-if="firstUploadError('partnership_agreement')" class="text-sm text-red-600 mt-1">{{ firstUploadError('partnership_agreement') }}</div>
          </div>
          <div v-if="uploadingAward?.partnership_agreement_file" class="text-xs text-gray-600">
            Uploading a new file will replace the current file:
            <strong>{{ uploadingAward.partnership_agreement_file }}</strong>
          </div>
        </div>
      </SubmissionWrapper>
    </modal-dialog>
  </div>
</template>
