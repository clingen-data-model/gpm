<script setup>
import { computed, ref, watch } from 'vue';
import { cloneDeep } from 'lodash-es';
import { api } from '@/http';
import ContactListSelector from '@/components/forms/ContactListSelector.vue';
import SearchSelect from '@/components/forms/SearchSelect.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import InactivationTextField from '@/components/groups/InactivationTextField.vue';

const props = defineProps({ group: { type: Object, required: true } });
const item = ref(null);
const answers = ref({});
const options = ref({ members: [], receiving_groups: [], receiving_members: [] });
const canStart = ref(false);
const loading = ref(true);
const busy = ref(false);
const errors = ref({});
const message = ref('');
const step = ref(0);
const confirmSubmit = ref(false);
const confirmDelete = ref(false);
const fileInput = ref(null);
const receiverLoading = ref(false);
let receiverSequence = 0;
const sections = ['A. Contact information', 'B. Transfer panel', 'C. Public-facing text', 'D. Notes and recordings', 'E. Final curation report', 'F. Publications', 'G. Additional opportunities'];
const base = computed(() => `/api/groups/${props.group.uuid}/inactivation-request`);
const editable = computed(() => item.value?.can_edit && !busy.value && !receiverLoading.value);
const isGcep = computed(() => props.group.is_gcep);
const transferring = computed(() => answers.value.transfer_planned === 'yes');
const uploadLimit = computed(() => item.value?.upload_max_kb ? `${Number((item.value.upload_max_kb / 1024).toFixed(2))} MB` : 'the server limit');
const publicationsUrl = computed(() => `/groups/${props.group.uuid}?tab=publications`);
const selectedReceiver = computed(() => options.value.receiving_groups.find(g => g.id === Number(answers.value.receiving_group_id)) || null);
const searchReceivers = text => options.value.receiving_groups.filter(g => `${g.name} ${g.affiliation_id} ${g.parent_name || ''}`.toLowerCase().includes((text || '').toLowerCase()));
const statusLabel = computed(() => ({ draft: 'Draft', submitted: 'Submitted', revision_requested: 'Revision Requested', pending_oc_review: 'Pending OC Review', approved: 'Approved' })[item.value?.status]);

function receive(data, replaceAnswers = true) {
  item.value = data;
  if (replaceAnswers) {
    answers.value = cloneDeep(data.answers);
    if (!Array.isArray(answers.value.liaisons)) {
      const legacy = answers.value.liaison;
      answers.value.liaisons = legacy && (legacy.member_id || legacy.name || legacy.email) ? [legacy] : [];
    }
    delete answers.value.liaison;
    answers.value.continuing_members = (answers.value.continuing_members || []).map(contact => ({ ...contact, name: contact.name || `${contact.first_name || ''} ${contact.last_name || ''}`.trim() }));
    answers.value.publications = (answers.value.publications || []).map(publication => ({ ...publication, pmc_compliant: publication.pmc_compliant || 'unknown' }));
    answers.value.publications_acknowledged = answers.value.publications_acknowledged || false;
  }
}
function failure(error) {
  if (error.response?.data?.data) applyPublications(error.response.data.data);
  errors.value = error.response?.data?.errors || { request: [error.response?.data?.message || 'Unable to save. Please try again.'] };
}
function applyPublications(data) {
  receive(data, false);
  answers.value.publications = cloneDeep(data.answers.publications);
  answers.value.publications_acknowledged = data.answers.publications_acknowledged;
  answers.value.legacy_publications = cloneDeep(data.answers.legacy_publications || []);
}
const refreshPublications = () => run(async () => {
  const response = await api.post(`${base.value}/publications/refresh`, {
    version: item.value.version, publications: answers.value.publications,
    publications_acknowledged: answers.value.publications_acknowledged,
  });
  applyPublications(response.data.data);
  message.value = 'Publication list refreshed. Other form changes are preserved; save your draft to store them.';
});
async function run(action) {
  busy.value = true;
  errors.value = {};
  message.value = '';
  try { await action(); } catch (error) { failure(error); } finally { busy.value = false; }
}
async function loadOptions() {
  const response = await api.get(`${base.value}/options`);
  options.value = response.data;
  if (answers.value.receiving_group_id && options.value.receiving_groups.some(g => g.id === Number(answers.value.receiving_group_id))) {
    const receiving = await api.get(`${base.value}/options`, { params: { receiving_group_id: answers.value.receiving_group_id } });
    options.value = receiving.data;
  }
}
async function load() {
  loading.value = true;
  item.value = null;
  errors.value = {};
  try {
    const response = await api.get(base.value);
    canStart.value = response.data.can_start;
    if (response.data.data) {
      receive(response.data.data);
      if (item.value.can_edit) await loadOptions();
    }
  } catch (error) { failure(error); } finally { loading.value = false; }
}
const start = () => run(async () => {
  receive((await api.post(base.value)).data.data);
  await loadOptions();
});
async function save() {
  receive((await api.put(base.value, { version: item.value.version, answers: answers.value })).data.data);
}
const saveDraft = () => run(async () => { await save(); message.value = 'Draft saved.'; });
const submit = () => run(async () => {
  await save();
  receive((await api.post(`${base.value}/submit`, { version: item.value.version })).data.data);
  confirmSubmit.value = false;
  message.value = 'Inactivation request submitted. Your group status has not changed.';
});
async function selectReceiver(receiver) {
  if (!editable.value || Number(receiver?.id) === Number(answers.value.receiving_group_id)) return;
  const sequence = ++receiverSequence;
  answers.value.receiving_group_id = receiver?.id || null;
  answers.value.receiving_name = receiver?.name || '';
  answers.value.receiving_affiliation_id = receiver?.affiliation_id || '';
  answers.value.receiving_parent_name = receiver?.parent_name || '';
  answers.value.receiving_liaisons = [];
  answers.value.transfer_attestation = false;
  options.value.receiving_members = [];
  if (!receiver) return;
  receiverLoading.value = true;
  try {
    const response = await api.get(`${base.value}/options`, { params: { receiving_group_id: receiver.id } });
    if (sequence !== receiverSequence) return;
    options.value = response.data;
    answers.value.receiving_liaisons = cloneDeep(response.data.receiving_liaison_suggestions || []);
  } catch (error) { failure(error); } finally { if (sequence === receiverSequence) receiverLoading.value = false; }
}
const upload = () => run(async () => {
  const file = fileInput.value?.files[0];
  if (!file) return;
  const data = new FormData();
  data.append('file', file);
  data.append('version', item.value.version);
  receive((await api.post(`${base.value}/documents`, data)).data.data, false);
  fileInput.value.value = '';
  message.value = 'Report uploaded. Save any other form changes separately.';
});
const removeDocument = doc => run(async () => {
  receive((await api.delete(`${base.value}/documents/${doc.uuid}`, { data: { version: item.value.version } })).data.data, false);
});
const deleteDraft = () => run(async () => {
  const response = await api.delete(base.value, { data: { uuid: item.value.uuid, version: item.value.version } });
  confirmDelete.value = false;
  confirmSubmit.value = false;
  item.value = null;
  answers.value = {};
  options.value = { members: [], receiving_groups: [], receiving_members: [] };
  step.value = 0;
  canStart.value = response.data.can_start;
  message.value = 'Draft and its uploaded documents permanently deleted.';
});
watch(() => props.group.uuid, uuid => { if (uuid) load(); }, { immediate: true });
</script>

<template>
  <div class="inactivation-request space-y-4">
    <p v-if="loading">
      Loading inactivation request…
    </p>
    <div v-if="Object.keys(errors).length" role="alert" class="p-3 bg-red-50 text-red-800 border rounded">
      <p>Please address the following:</p>
      <ul>
        <li v-for="(messages, field) in errors" :key="field">
          <strong>{{ field }}:</strong> {{ messages.join(' ') }}
        </li>
      </ul>
    </div>
    <p v-if="message" role="status" class="p-3 bg-green-50 rounded">
      {{ message }}
    </p>
    <template v-if="!loading && !item && !Object.keys(errors).length">
      <p>Use this form when your Expert Panel plans to become inactive. It records contacts, transfer plans, final curations, and publications for review. Starting or submitting a request does not change the group's status.</p>
      <button v-if="canStart" class="btn" :disabled="busy" @click="start">
        Start Inactivation Request
      </button>
      <p v-else>
        A current coordinator, Super User, or Super Admin can start this request.
      </p>
    </template>
    <template v-if="item">
      <p><strong>Status: {{ statusLabel }}</strong><span v-if="item.submitted_at"> · Submitted {{ new Date(item.submitted_at).toLocaleDateString() }}</span></p>
      <p v-if="!item.can_edit" class="p-3 bg-gray-100 rounded">
        This request is read-only.
      </p>
      <nav aria-label="Inactivation form sections" class="flex flex-wrap gap-2">
        <button v-for="(section, index) in sections" :key="section" type="button" class="btn btn-xs section-button" :class="{ 'section-active': step === index }" :aria-current="step === index ? 'step' : undefined" @click="step = index">
          {{ section }}
        </button>
      </nav>
      <h3>{{ sections[step] }}</h3>
      <fieldset :disabled="!editable" class="space-y-3">
        <template v-if="step === 0">
          <label>Expert Panel affiliation name<input v-model="answers.group_name"></label>
          <label>Affiliation ID<input v-model="answers.affiliation_id"></label>
          <label>Parent CDWG (if applicable)<input v-model="answers.parent_name"></label>
          <p>The liaison is a current or former member willing to respond to future ClinGen communications on behalf of this panel.</p>
          <ContactListSelector v-model="answers.liaisons" :members="options.members" :disabled="!editable" label="EP liaisons" />
          <label>Date of request<input v-model="answers.request_date" type="date"></label>
        </template>
        <template v-if="step === 1">
          <p v-if="isGcep">
            Gene-disease records must transfer to another GCEP for recuration.
          </p>
          <label v-else>Will classifications transfer to another panel?<select v-model="answers.transfer_planned"><option :value="null">Select…</option><option value="yes">Yes</option><option value="no">No transfer planned</option></select></label>
          <label v-if="answers.transfer_planned === 'no'" class="check"><input v-model="answers.no_transfer_attestation" type="checkbox">Our panel does not plan to transfer approved variants. I understand that, when our panel becomes inactive, variants will be downgraded from 3 stars to 1 star over time per FDA guidance if they are not re-reviewed.</label>
          <template v-if="transferring">
            <div v-if="item.can_edit">
              <p class="font-medium">
                Receiving Expert Panel
              </p>
              <SearchSelect :key="answers.receiving_group_id || 'none'" :model-value="selectedReceiver" :options="options.receiving_groups" :search-function="searchReceivers" :disabled="!editable" show-options-on-focus placeholder="Search panels by name or affiliation ID" @update:model-value="selectReceiver">
                <template #option="{ option }">
                  <div class="font-medium">
                    {{ option.name }}
                  </div><div class="text-xs text-gray-600">
                    {{ option.affiliation_id }}<span v-if="option.parent_name"> · {{ option.parent_name }}</span>
                  </div>
                </template>
              </SearchSelect>
            </div>
            <p v-if="receiverLoading" role="status">
              Loading receiving panel contacts…
            </p>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-gray-50 border rounded p-3">
              <dt class="font-medium">
                Receiving panel name
              </dt><dd>{{ answers.receiving_name || '—' }}</dd>
              <dt class="font-medium">
                Receiving affiliation ID
              </dt><dd>{{ answers.receiving_affiliation_id || '—' }}</dd>
              <dt class="font-medium">
                Receiving parent CDWG
              </dt><dd>{{ answers.receiving_parent_name || '—' }}</dd>
            </dl>
            <ContactListSelector v-model="answers.receiving_liaisons" :members="options.receiving_members" :disabled="!editable" label="Receiving EP liaisons" />
            <label v-if="!isGcep">Date of transfer discussion<input v-model="answers.transfer_discussion_date" type="date"></label>
            <label class="check"><input v-model="answers.transfer_attestation" type="checkbox">I attest that we have discussed transfer plans with the receiving panel and notified the appropriate CDWG.</label>
          </template>
        </template>
        <template v-if="step === 2">
          <p>Describe the panel's history, initial scope, completion of its work, and resulting publications for its ClinGen webpage. {{ isGcep ? 'Identify the receiving GCEP and its plans for periodic recuration.' : 'Include the Step 1 approval year and a link to the latest CSpec specifications.' }}</p>
          <InactivationTextField v-model="answers.public_text" :disabled="!editable" label="Public-facing explanation" />
        </template>
        <template v-if="step === 3">
          <p>Share available research, notes, meeting minutes, supporting documents, and recordings with the receiving liaison in a manner agreed by both groups. For help using ClinGen's Google Site, contact clingen@clinicalgenome.org.</p>
          <p v-if="!transferring">
            No transfer is planned. This section is optional.
          </p>
          <InactivationTextField v-model="answers.materials_access" :disabled="!editable" label="Access arrangements or links (optional)" />
        </template>
        <template v-if="step === 4">
          <p v-if="isGcep">
            Upload a GeneTracker or GCI report with gene name, disease name and MONDO ID, mode of inheritance, final classification, approval date, and record/publication status. Include unpublished curations and reasons, plus any pre-2017 Gene Curation Express records absent from GCI. Update GCI statuses as needed.
          </p>
          <p v-else>
            Upload an Evidence Repository or VCI report with gene name, variant HGVS, disease name and MONDO ID, inheritance pattern, final classification, approval date, record status, and ClinVar SCV IDs where applicable. Include unpublished curations and reasons for their unfinished status. Update VCI statuses and publish/submit approved variants as appropriate.
          </p>
          <template v-if="item.can_edit">
            <label>Final curation report (PDF, CSV, text, Excel, or Word; maximum {{ uploadLimit }})<input ref="fileInput" type="file" accept=".pdf,.csv,.txt,.xls,.xlsx,.doc,.docx"></label><button class="btn btn-xs" @click="upload">
              Upload report
            </button>
          </template>
        </template>
        <template v-if="step === 5">
          <p>All publications recorded for this group are included below. Review PMC compliance for each publication; a PMCID alone does not establish compliance.</p>
          <p v-if="item.status === 'draft'">Missing a publication? Add it in the <a :href="publicationsUrl" target="_blank" rel="noopener" class="underline">group's Publications tab</a>, then refresh this list.</p>
          <button v-if="item.can_edit" type="button" class="btn btn-xs" @click="refreshPublications">Refresh publications</button>
          <div v-if="answers.publications.length" class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead><tr><th>PMID</th><th>Title</th><th>PMCID</th><th>PMC compliant</th></tr></thead>
              <tbody>
                <tr v-for="(publication, index) in answers.publications" :key="publication.publication_id || index" class="border-t">
                  <td>{{ publication.pmid || '?' }}</td><td>{{ publication.title }}</td><td>{{ publication.pmcid || '?' }}</td>
                  <td><select v-model="publication.pmc_compliant" :aria-label="`PMC compliance for ${publication.title}`"><option value="yes">Yes</option><option value="no">No</option><option value="unknown">Unknown</option></select></td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else>No publications are recorded for this group.</p>
          <p v-if="answers.legacy_publications?.length" class="text-sm">Previously saved request-only or removed publications are retained with this request. Add any missing entries to the group's Publications tab before refreshing.</p>
          <label class="check"><input v-model="answers.publications_acknowledged" type="checkbox">I have reviewed the group's publication list and confirm it is complete and accurate.</label>
        </template>
        <template v-if="step === 6">
          <p>Optional: select members interested in contributing to other Expert Panels. You may also enter a contact manually if they are not in the GPM member list.</p>
          <ContactListSelector v-model="answers.continuing_members" :members="options.members" :disabled="!editable" allow-manual label="Members interested in additional opportunities" />
        </template>
      </fieldset>
      <ul v-if="step === 4" class="space-y-2">
        <li v-for="doc in item.documents" :key="doc.uuid">
          <a :href="doc.download_url" class="underline">{{ doc.filename }}</a><button v-if="item.can_edit" class="btn btn-xs ml-3" :disabled="busy" @click="removeDocument(doc)">
            Remove
          </button>
        </li>
        <li v-if="!item.documents.length">
          No report uploaded.
        </li>
      </ul>
      <div class="flex flex-wrap gap-2 border-t pt-3">
        <button class="btn" :disabled="step === 0" @click="step--">
          Previous
        </button>
        <button class="btn" :disabled="step === 6" @click="step++">
          Next
        </button>
        <button v-if="item.can_edit" class="btn" :disabled="!editable" @click="saveDraft">
          Save draft
        </button>
        <button v-if="item.can_edit && step === 6" class="btn" :disabled="!editable" @click="confirmSubmit = true">
          Submit request
        </button>
        <button v-if="item.status === 'draft' && item.can_delete" type="button" class="btn btn-xs text-red-700 border-red-300 ml-auto" :disabled="!editable" @click="confirmDelete = true">
          Delete Draft
        </button>
      </div>
      <div v-if="confirmSubmit && item.can_edit" class="border rounded p-3 space-y-3">
        <p>Submit this request? The form and its reports will become read-only. The official group status will remain unchanged.</p>
        <button class="btn" :disabled="!editable" @click="submit">
          Confirm submission
        </button><button class="btn ml-2" :disabled="busy" @click="confirmSubmit = false">
          Keep editing
        </button>
      </div>
    </template>
    <ModalDialog v-model="confirmDelete" title="Delete Inactivation Request Draft?" size="sm" :hide-close="busy">
      <p>This draft, all its associated form data, and all uploaded documents will be permanently deleted. This cannot be undone. You can start a new request later.</p>
      <div v-if="Object.keys(errors).length" role="alert" class="text-red-700 mt-3">
        <p v-for="(messages, field) in errors" :key="field">
          {{ messages.join(' ') }}
        </p>
      </div>
      <div class="flex gap-2 mt-4">
        <button type="button" class="btn red" :disabled="!editable" @click="deleteDraft">
          Permanently Delete Draft
        </button>
        <button type="button" class="btn" :disabled="busy" @click="confirmDelete = false">
          Cancel
        </button>
      </div>
    </ModalDialog>
  </div>
</template>

<style scoped>
label { display: block; font-weight: 500; }
input:not([type=checkbox]), select, textarea { display: block; width: 100%; border: 1px solid #d1d5db; border-radius: .25rem; padding: .5rem; margin-top: .25rem; font-weight: normal; }
.check { display: flex; gap: .5rem; align-items: baseline; }
fieldset:disabled input, fieldset:disabled textarea, fieldset:disabled select { background: #f9fafb; color: #374151; }
.inactivation-request .section-button { background: #f3f4f6; border: 1px solid #d1d5db; color: #374151; white-space: normal; text-align: left; }
.inactivation-request .section-button.section-active { background: #2563eb; border-color: #1d4ed8; color: white; font-weight: 600; }
.inactivation-request .section-button:focus-visible { outline: 2px solid #1d4ed8; outline-offset: 3px; }
</style>
