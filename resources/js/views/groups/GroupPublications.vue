<script setup>
import { ref, watch, computed, reactive, onActivated, defineExpose } from "vue";
import { api } from "@/http";
import useOpenAlex from "@/composables/useOpenAlex";
import { hasRole } from '@/auth_utils'
import { useRoute } from 'vue-router'

const route = useRoute();

const props = defineProps({ group: { type: Object, required: true } });

const loading = ref(false);
const loadedFor = ref(null);
const items = ref([]);
const error = ref("");
const pubClient = useOpenAlex();

const addModal = reactive({
  open: false,
  raw: "",
  loading: false,
  error: "",
  preview: null,
});

const detailsModal = reactive({
  open: false,
  item: null,
  copyLabel: 'Copy JSON',
});

const groupUuid = computed(() => props.group?.uuid);

async function fetchPublications() {
    if (!groupUuid.value) return;
    loading.value = true;
    error.value = "";
    try {
        const { data } = await api.get(`/api/groups/${groupUuid.value}/publications`, { });
        items.value = data;
        if (detailsModal.open) {
            detailsModal.item = data.find(p => p.id === detailsModal.item?.id) ?? null;
        }
        loadedFor.value = groupUuid.value
    } catch (e) {
        error.value = "Failed to load publications.";
    } finally {
        loading.value = false;
    }
}

function refresh() { return fetchPublications(); }
defineExpose({ refresh });

async function doPreview() {
  addModal.error = "";
  addModal.preview = null;
  addModal.loading = true;
  const q = addModal.raw?.trim();
  if (!q) {
    addModal.loading = false;
    addModal.error = "Please enter a DOI, PMID, or PMCID.";
    return;
  }
  try {
    const res = await pubClient.lookupIdentifier(q);
    if (!res) throw new Error("No match found.");
    addModal.preview = res;
  } catch (e) {
    addModal.error = e?.message || "Lookup failed.";
  } finally {
    addModal.loading = false;
  }
}

async function savePublication() {
  addModal.error = ""

  if (!addModal.preview) {
    addModal.error = "Preview first.";
    return
  }

  try {
    await api.post(`/api/groups/${groupUuid.value}/publications`, addModal.preview)
    addModal.raw = "";
    addModal.preview = null;
    addModal.open = false
    await fetchPublications()
  } catch (e) {
    addModal.error = e?.response?.data?.message || "Failed to add publication."
  }
}


async function removePublication(pub) {
    if (!confirm("Remove this publication entry?")) return;
    try {
        await api.delete(`/api/groups/${groupUuid.value}/publications/${pub.id}`);
        await fetchPublications();
    } catch (e) {
        error.value = "Failed to remove publication.";
    }
}

/** DETAIL SECTION */
function showDetails(p){
  linkQuery.value = '';
  linkResults.value = [];
  linkTarget.value = '';
  linkError.value = '';
  detailsModal.item = p;
  detailsModal.open = true;
}
function closeDetails(){
  detailsModal.open = false;
  detailsModal.item = null;
}

const linkQuery = ref('');
const linkResults = ref([]);
const linkTarget = ref('');
const linkError = ref('');
const linkBusy = ref(false);
const canEdit = computed(() => hasRole('super-user') || hasRole('super-admin') || hasRole('coordinator', props.group));
async function searchLinks() {
  linkError.value = '';
  try {
    const { data } = await api.get(`/api/groups/${groupUuid.value}/publications/search`, { params: { query: linkQuery.value } });
    linkResults.value = data.filter(p => p.id !== detailsModal.item.id);
  } catch (e) { linkError.value = e?.response?.data?.message || 'Search failed.'; }
}
async function saveLink(target) {
  linkBusy.value = true;
  linkError.value = '';
  try {
    const id = detailsModal.item.id;
    await api.put(`/api/groups/${groupUuid.value}/publications/${id}/link`, { published_publication_id: target || null });
    await fetchPublications();
    detailsModal.item = items.value.find(p => p.id === id);
    linkTarget.value = '';
  } catch (e) { linkError.value = e?.response?.data?.message || 'Unable to save link.'; }
  finally { linkBusy.value = false; }
}
const relatedPublications = (p) => [
  { label: 'Published version', publication: p?.published_publication },
  { label: 'Linked preprint', publication: p?.preprint },
].filter(related => related.publication);
const pmcidOf = (p) => p?.meta?.pmcid?.id || (p?.source === 'pmcid' ? p.identifier : null);
const publicationUrl = (p) => `/groups/${p.group?.uuid || groupUuid.value}?tab=publications&publication=${p.id}`;
function openRelated(event, p) {
  const local = items.value.find(item => item.id === p.id);
  if (local && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
    event.preventDefault();
    showDetails(local);
  }
}
watch(() => [route.query.publication, items.value], ([id]) => {
  if (!id) return;
  const publication = items.value.find(p => String(p.id) === String(id));
  if (publication) showDetails(publication);
}, { immediate: true });
const prettyMeta = computed(() => JSON.stringify(detailsModal.item?.meta ?? {}, null, 2));

const metaEntries = computed(() => {
    const m = detailsModal.item?.meta ?? {};
    return Object.entries(m).map(([k, v]) => [String(k).toUpperCase(), v]);
});

function fmt(val) {
    if (val == null) return '—';
    if (Array.isArray(val)) return val.join(', ');
    if (typeof val === 'object') return JSON.stringify(val, null, 2);
    return String(val);
}

const copyLabel = ref('Copy JSON');
let copyTimer;
async function copyMeta(){
    try {
        await navigator.clipboard.writeText(prettyMeta.value || "{}");
        detailsModal.copyLabel = 'JSON copied!';
        clearTimeout(copyTimer);
        copyTimer = setTimeout(() => (detailsModal.copyLabel = 'Copy JSON'), 2200);
    } catch {}
}

function detailsLink() {
  const row = detailsModal.item;
  return row?.link || row?.meta?.doi?.link || row?.meta?.pmid?.link || row?.meta?.pmcid?.link || null;
}

watch(groupUuid, (val, old) => { if (val && val !== old) fetchPublications(); }, { immediate: true });
onActivated(() => {
    if (groupUuid.value && loadedFor.value !== groupUuid.value && items.value.length === 0) fetchPublications();
});

function clearAddModal() {
  addModal.raw   = ''
  addModal.error = ''
  addModal.preview  = null
  addModal.open = false
}
</script>

<template>
    <section class="space-y-3">
        <header class="flex items-center justify-between">
            <h3 class="text-xl font-semibold">Publications</h3>
            <button v-if="hasRole('super-user') || hasRole('super-admin') || hasRole('coordinator', group)" class="btn btn-primary btn-sm" @click="addModal.open = true">Add publication</button>
        </header>
        <p v-if="error" class="text-red-600">{{ error }}</p>
        <div v-if="!loading && items.length === 0" class="text-gray-600">No publications added yet.</div>
        <div v-else-if="loading">Loading...</div>
        <div v-else class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Title</th>
                        <th class="px-3 py-2 text-left">Journal</th>
                        <th class="px-3 py-2 text-left">Identifiers</th>
                        <th class="px-3 py-2 text-left">Published</th>
                        <th class="px-3 py-2 text-left">Added to GPM</th>
                        <th class="px-3 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in items" :key="p.id" class="border-t">
                        <td class="px-3 py-2">
                            <span class="inline-block px-2 py-0.5 rounded text-xs" :class="p.pub_type === 'preprint' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'">{{ p.pub_type }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="font-medium">
                                    <span v-if="!p.link">{{ p.meta?.title }}</span>
                                    <a v-else :href="p.link" target="_blank" rel="noopener noreferrer" class="underline">
                                        {{ p.meta?.title }}
                                    </a>
                                </div>

                                <a v-if="p.link" :href="p.link" target="_blank" rel="noopener noreferrer" class="inline-flex items-center opacity-80 hover:opacity-100" aria-label="Open publication in new tab" title="Open link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="text-gray-600">
                                        <path d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42 9.3-9.29H14V3z"/>
                                        <path d="M5 5h6v2H7v10h10v-4h2v6H5V5z"/>
                                    </svg>
                                </a>
                            </div>
                            <div v-for="{ label, publication: linked } in relatedPublications(p)" :key="label" class="text-xs mt-1">
                                {{ label }}:
                                <a :href="publicationUrl(linked)" class="underline" @click="openRelated($event, linked)">{{ linked.meta?.title || linked.identifier }}</a>
                                <span v-if="pmcidOf(linked)"> (PMCID: {{ pmcidOf(linked) }})</span>
                            </div>
                        </td>

                        <td class="px-3 py-2">{{ p.meta?.journal }}</td>
                        <td class="px-3 py-2">{{ String(p.source || '').toUpperCase() }}: {{ p.identifier }}</td>
                        <td class="px-3 py-2">{{ formatDate(p.published_at) }}</td>
                        <td class="px-3 py-2">{{ formatDate(p.created_at) }}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-2">
                                <button class="btn btn-xs inline-flex items-center shrink-0" @click="showDetails(p)" title="View details"> Details </button>
                                <button v-if="hasRole('super-user') || hasRole('super-admin') || hasRole('coordinator', group)" class="btn btn-xs inline-flex items-center shrink-0" @click="removePublication(p)" title="Remove publication" > Remove </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <modal-dialog v-model="addModal.open" title="Add a publication">
          <div class="space-y-3">
            <p class="text-sm text-gray-600">
              Paste a <strong>PMCID, PMID, or DOI</strong> to preview the record before saving. <strong>PMCID is preferred when available</strong>.
            </p>

            <div class="flex gap-2">
              <input
                v-model="addModal.raw"
                class="w-full border rounded px-3 py-2"
                placeholder="e.g., 10.1038/s41586-020-2649-2 · PMID:29456894 · PMC1234567"
                @keydown.enter.prevent="doPreview"
              />
              <button class="btn" :disabled="addModal.loading || !addModal.raw" @click="doPreview">
                {{ addModal.loading ? 'Looking…' : 'Preview' }}
              </button>
            </div>

            <p v-if="addModal.error" class="text-sm text-red-600">{{ addModal.error }}</p>
            <div v-if="addModal.preview" class="rounded border p-3 space-y-1">
              <div class="font-medium">{{ addModal.preview.title_plain || 'Untitled' }}</div>

              <div class="text-sm text-gray-600">
                {{ addModal.preview.meta.journal || '—' }}
                · {{ addModal.preview.meta.type || '—' }}
                · {{ formatDate(addModal.preview.meta.published_at) || '—' }}
              </div>

              <div class="text-xs text-gray-600">
                DOI: <span class="font-mono">{{ addModal.preview.meta?.doi?.id || '—' }}</span>
                <span class="mx-2">|</span>
                PMID: <span class="font-mono">{{ addModal.preview.meta?.pmid?.id || '—' }}</span>
                <span class="mx-2">|</span>
                PMCID: <span class="font-mono">{{ addModal.preview.meta?.pmcid?.id || '—' }}</span>
              </div>

              <div v-if="addModal.preview.meta.authors?.length" class="text-xs text-gray-600">
                {{ addModal.preview.meta.authors.join('; ') }}
              </div>

              <div class="flex items-center gap-2 pt-2">
                <a
                  v-if="addModal.preview.link"
                  :href="addModal.preview.link"
                  target="_blank"
                  rel="noopener"
                  class="underline text-sm"
                >
                  Open
                </a>
                <button class="btn" @click="savePublication">Save</button>
                <button class="btn" @click="clearAddModal" type="button">Clear</button>
              </div>
            </div>
          </div>
        </modal-dialog>
        <modal-dialog v-model="detailsModal.open" title="Publication Details" @closed="closeDetails">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="text-sm text-gray-600">
                        <div><strong class="mr-1 capitalize">SOURCE:</strong>{{ detailsModal.item?.source }}</div>
                        <div><strong class="mr-1">IDENTIFIER:</strong>{{ detailsModal.item?.identifier }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="btn transition-colors" :class="detailsModal.copyLabel === 'JSON copied!' ? 'bg-green-100 text-green-800' : ''" @click="copyMeta">{{ detailsModal.copyLabel }}</button>
                        <a v-if="detailsLink()" :href="detailsLink()" target="_blank" rel="noopener noreferrer" class="btn inline-flex items-center" title="Open link in new tab">
                            Open publication on new tab
                        </a>
                    </div>
                </div>

                <div class="border rounded p-3 space-y-2">
                    <div v-for="{ label, publication: linked } in relatedPublications(detailsModal.item)" :key="label" data-testid="publication-relationship">
                        <strong>{{ label }}:</strong>
                        <a :href="publicationUrl(linked)" class="underline" @click="openRelated($event, linked)">{{ linked.meta?.title || linked.identifier }}</a>
                        <span v-if="pmcidOf(linked)"> (PMCID: {{ pmcidOf(linked) }})</span>
                    </div>
                    <div v-if="canEdit && !detailsModal.item?.preprint" class="space-y-2">
                        <p>Link this preprint to an existing published article.</p>
                        <div class="flex gap-2">
                            <input v-model="linkQuery" aria-label="Find existing publication" placeholder="Title or identifier" class="border rounded p-2" @keydown.enter.prevent="searchLinks" />
                            <button class="btn" :disabled="linkQuery.trim().length < 2 || linkBusy" @click="searchLinks">Search</button>
                        </div>
                        <select v-model="linkTarget" aria-label="Published article" class="border rounded p-2 w-full">
                            <option value="">Select an existing publication</option>
                            <option v-for="candidate in linkResults" :key="candidate.id" :value="candidate.id">{{ candidate.meta?.title || candidate.identifier }} - {{ candidate.identifier }} - {{ candidate.group?.name }}</option>
                        </select>
                        <button class="btn" :disabled="!linkTarget || linkBusy" @click="saveLink(linkTarget)">Save link</button>
                        <button v-if="detailsModal.item?.published_publication_id" class="btn ml-2" :disabled="linkBusy" @click="saveLink(null)">Remove link</button>
                    </div>
                    <p v-if="detailsModal.item?.preprint" class="text-sm text-gray-600">Edit this link from the preprint's group publication details.</p>
                    <p v-if="linkError" class="text-red-600">{{ linkError }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm max-h-[65vh] overflow-auto border rounded p-3">
                    <div v-for="[k, v] in metaEntries" :key="k" class="flex flex-col">
                        <div class="text-xs text-gray-500 tracking-wider uppercase">{{ k }}</div>
                        <pre v-if="typeof v === 'object'" class="whitespace-pre-wrap break-words">{{ fmt(v) }}</pre>
                        <div v-else class="break-words">{{ fmt(v) }}</div>
                    </div>
                </div>
            </div>
        </modal-dialog>
  </section>
</template>
