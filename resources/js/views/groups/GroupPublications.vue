<script setup>
import { ref, watch, computed, reactive, onActivated, defineExpose } from "vue";
import SearchSelect from '@/components/forms/SearchSelect.vue';
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

const visibleItems = computed(() => items.value.filter(p => !p.published_publication_id));
const groupUuid = computed(() => props.group?.uuid);

async function fetchPublications() {
    if (!groupUuid.value) return;
    loading.value = true;
    error.value = "";
    try {
        const { data } = await api.get(`/api/groups/${groupUuid.value}/publications`, { params: { include_linked: true } });
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
  linkDirection.value = p.preprint ? 'preprint' : (p.pub_type === 'published' && !p.published_publication_id ? 'preprint' : 'published');
  detailsModal.item = p;
  detailsModal.open = true;
}
function closeDetails(){
  detailsModal.open = false;
  detailsModal.item = null;
}

const linkDirection = ref('published');
const linkQuery = ref('');
let searchSequence = 0;
const linkResults = ref([]);
const linkTarget = ref('');
const linkSelection = ref(null);
const publicationSelect = ref(null);
const hasRelationship = computed(() => !!(detailsModal.item?.published_publication_id || detailsModal.item?.preprint));
function selectLink(publication) {
  linkSelection.value = publication;
  linkTarget.value = publication?.id || '';
}
watch(linkTarget, value => { if (!value) linkSelection.value = null; });
const linkError = ref('');
const linkBusy = ref(false);
const canEdit = computed(() => hasRole('super-user') || hasRole('super-admin') || hasRole('coordinator', props.group));
async function searchLinks() {
  const sequence = ++searchSequence;
  const id = detailsModal.item?.id;
  const query = linkQuery.value.trim();
  linkError.value = '';
  try {
    const { data } = await api.get(`/api/groups/${groupUuid.value}/publications/search`, { params: { query, publication_id: id } });
    if (sequence === searchSequence && detailsModal.item?.id === id && linkQuery.value.trim() === query) {
      linkResults.value = data;
      if (publicationSelect.value && !linkSelection.value) {
        publicationSelect.value.filteredOptions = data;
        publicationSelect.value.hasFocus = true;
      }
      return data;
    }
  } catch (e) {
    if (sequence === searchSequence) linkError.value = e?.response?.data?.message || 'Search failed.';
  }
}
function openLinkOptions() {
  if (!linkSelection.value) searchLinks();
}
function updateSelectorQuery(event) {
  linkQuery.value = event.target.value;
  if (!linkQuery.value.trim()) searchLinks();
}
async function searchPublicationOptions(query) {
  linkQuery.value = query;
  return (await searchLinks()) || linkResults.value;
}
watch(linkDirection, () => { linkTarget.value = ''; });
async function saveLink(target) {
  linkBusy.value = true;
  linkError.value = '';
  try {
    const item = detailsModal.item;
    const id = item.id;
    const reverse = target ? linkDirection.value === 'preprint' : !!item.preprint;
    const preprintId = reverse ? (target || item.preprint.id) : id;
    const publishedId = target ? (reverse ? id : target) : null;
    const expected = reverse ? (target ? null : id) : (item.published_publication_id || null);
    // Always write the relationship on the preprint, whichever side is open.
    await api.put(`/api/groups/${groupUuid.value}/publications/${preprintId}/link`, {
      published_publication_id: publishedId,
      expected_published_publication_id: expected,
    });
    await fetchPublications();
    detailsModal.item = items.value.find(p => p.id === id);
    linkTarget.value = '';
    linkResults.value = [];
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
        <div v-if="!loading && visibleItems.length === 0" class="text-gray-600">No publications added yet.</div>
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
                    <tr v-for="p in visibleItems" :key="p.id" class="border-t">
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
                                <span> ({{ String(linked.source || '').toUpperCase() }}: {{ linked.identifier }})</span>
                                <span v-if="linked.published_at"> ? {{ formatDate(linked.published_at) }}</span>
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
                        <span> ({{ String(linked.source || '').toUpperCase() }}: {{ linked.identifier }})</span>
                                <span v-if="linked.published_at"> ? {{ formatDate(linked.published_at) }}</span>
                    </div>
                    <div v-if="canEdit" class="min-w-0 space-y-2">
                        <button v-if="hasRelationship" class="btn" :disabled="linkBusy" @click="saveLink(null)">Remove link</button>
                        <template v-else>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <label class="flex min-w-0 items-center gap-2 sm:shrink-0">
                                    <span class="shrink-0">Link to</span>
                                    <select v-model="linkDirection" aria-label="Relationship direction" :disabled="linkBusy" class="min-w-0 flex-1 rounded border p-2">
                                        <option value="published">Published version</option>
                                        <option value="preprint">Preprint</option>
                                    </select>
                                </label>
                                <div class="flex min-w-0 flex-1 gap-2">
                                    <input v-model="linkQuery" @input="searchLinks" aria-label="Find existing publication" placeholder="Title or identifier" class="min-w-0 w-full flex-1 rounded border p-2" @keydown.enter.prevent="searchLinks" />
                                    <button class="btn shrink-0" :disabled="linkBusy" @click="searchLinks">Search</button>
                                </div>
                            </div>
                            <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-start">
                                <SearchSelect
                                    :key="`${detailsModal.item?.id}-${linkDirection}`"
                                    ref="publicationSelect"
                                    :model-value="linkSelection"
                                    class="publication-link-select min-w-0 flex-1"
                                    :aria-label="linkDirection === 'published' ? 'Published article' : 'Preprint'"
                                    :disabled="linkBusy"
                                    :options="linkResults"
                                    :search-function="searchPublicationOptions"
                                    :options-height="240"
                                    :throttle="0"
                                    placeholder="Select a publication"
                                    @focusin="openLinkOptions"
                                    @input="updateSelectorQuery"
                                    @update:model-value="selectLink"
                                >
                                    <template #option="{ option }">
                                        <div class="whitespace-normal [overflow-wrap:anywhere]">{{ option.meta?.title || option.identifier }}</div>
                                        <div class="text-xs text-gray-600 whitespace-normal [overflow-wrap:anywhere]">{{ String(option.source || '').toUpperCase() }}: {{ option.identifier }}</div>
                                    </template>
                                    <template #selection-label="{ selection }">
                                        <span class="block whitespace-normal [overflow-wrap:anywhere]">{{ selection.meta?.title || selection.identifier }}</span>
                                        <span class="block text-xs whitespace-normal [overflow-wrap:anywhere]">{{ String(selection.source || '').toUpperCase() }}: {{ selection.identifier }}</span>
                                    </template>
                                </SearchSelect>
                                <button class="btn shrink-0 self-end sm:self-start" :disabled="!linkTarget || linkBusy" @click="saveLink(linkTarget)">Save link</button>
                            </div>
                        </template>
                    </div>
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

<style scoped>
.publication-link-select :deep(.selection) {
    min-width: 0;
    flex: 1 1 0%;
}
.publication-link-select :deep(.selection > label) {
    min-width: 0;
    flex: 1;
    white-space: normal;
}
.publication-link-select :deep(.remove-btn) {
    flex-shrink: 0;
}
.publication-link-select :deep(.result-container),
.publication-link-select :deep(.option-list) {
    max-width: 100%;
    overflow-x: hidden;
}
.publication-link-select :deep(.option-list) {
    overflow-y: auto;
}
</style>
