<script setup>
import { ref, watch, computed, reactive, onActivated, defineExpose } from "vue";
import { api } from "@/http";
import usePublicationLookup from "@/composables/usePublicationLookup";

const props = defineProps({ group: { type: Object, required: true } });

const loading = ref(false);
const loadedFor = ref(null);
const items = ref([]);
const error = ref("");
const pubClient = usePublicationLookup();

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
    addModal.error = "Please enter an identifier.";
    return;
  }
  try {
    const res = await pubClient.fetchFromUrl(q);
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

  const payload = {
    source:      addModal.preview.doi ? 'doi' : (addModal.preview.pmid ? 'pmid' : (addModal.preview.pmcid ? 'pmcid' : 'url')),
    identifier:  addModal.preview.doi ?? addModal.preview.pmid ?? addModal.preview.pmcid ?? addModal.raw.trim(),
    link:        addModal.preview.url ?? null,
    pub_type:    addModal.preview.pubType ?? null,
    published_at: addModal.preview.date ?? addModal.preview.firstPublicationDate ?? null,
    meta:        addModal.preview,
  }

  try {
    await api.post(`/api/groups/${groupUuid.value}/publications`, payload)
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
  detailsModal.item = p;
  detailsModal.open = true;
}
function closeDetails(){
  detailsModal.open = false;
  detailsModal.item = null;
}

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

function detailsLink(){
    const row = detailsModal.item;
    return row?.meta?.url || null;
}

watch(groupUuid, (val, old) => { if (val && val !== old) fetchPublications(); }, { immediate: true });
onActivated(() => {
    if (groupUuid.value && loadedFor.value !== groupUuid.value && items.value.length === 0) fetchPublications();
});

function titleOf(p) {
    return p.meta?.title || "(title pending)";
}
function journalOf(p) {
    return p.meta?.journal || p.meta?.journalTitle || "";
}
function dateOf(p) {
    return p.published_at || p.meta?.firstPublicationDate || p.meta?.pubdate || "";
}
function typeOf(p) {
    return p.pub_type || (p.meta?.pubType === "preprint" ? "preprint" : (p.meta?.pubType || "published"));
}
function idsOf(p) {
    const pmid = p.meta?.pmid || p.meta?.pmId || p.pmid;
    const pmcid = p.meta?.pmcid || p.pmcid;
    const doi = p.meta?.doi || p.doi;
    return { pmid, pmcid, doi };
}
function urlOf(p) {
    return p.link || p.meta?.url || null;
}

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
            <button class="btn btn-primary btn-sm" @click="addModal.open = true">Add publication</button>
        </header>
        <p v-if="error" class="text-red-600">{{ error }}</p>
        <div v-if="!loading && items.length === 0" class="text-gray-600">No publications added yet.</div>
        <div v-if="loading">Loading...</div>
        <div v-else class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Title</th>
                        <th class="px-3 py-2 text-left">Journal</th>
                        <th class="px-3 py-2 text-left">Identifiers</th>
                        <th class="px-3 py-2 text-left">Published</th>
                        <th class="px-3 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in items" :key="p.id" class="border-t">
                        <td class="px-3 py-2">
                            <span class="inline-block px-2 py-0.5 rounded text-xs" :class="typeOf(p) === 'preprint' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'">{{ typeOf(p) }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="font-medium">
                                    <span v-if="!urlOf(p)">{{ titleOf(p) }}</span>
                                    <a v-else :href="urlOf(p)" target="_blank" rel="noopener noreferrer" class="underline">
                                        {{ titleOf(p) }}
                                    </a>
                                </div>

                                <a v-if="urlOf(p)" :href="urlOf(p)" target="_blank" rel="noopener noreferrer" class="inline-flex items-center opacity-80 hover:opacity-100" aria-label="Open publication in new tab" title="Open link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" class="text-gray-600">
                                        <path d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42 9.3-9.29H14V3z"/>
                                        <path d="M5 5h6v2H7v10h10v-4h2v6H5V5z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>

                        <td class="px-3 py-2">{{ journalOf(p) }}</td>
                        <td class="px-3 py-2">
                            <template v-if="idsOf(p).pmid">
                                <span class="mr-2">PMID: {{ idsOf(p).pmid }}</span>
                            </template>
                            <template v-if="idsOf(p).pmcid">
                                <span class="mr-2">PMCID: {{ idsOf(p).pmcid }}</span>
                            </template>
                            <template v-if="idsOf(p).doi">
                                <span class="mr-2">DOI: {{ idsOf(p).doi }}</span>
                            </template>
                            <div class="text-xs text-gray-500">
                                source={{ p.source }} • id={{ p.identifier }}
                            </div>
                        </td>
                        <td class="px-3 py-2">{{ dateOf(p) }}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-2">
                                <button class="btn btn-xs inline-flex items-center shrink-0" @click="showDetails(p)" title="View details"> Details </button>
                                <button class="btn btn-xs inline-flex items-center shrink-0" @click="removePublication(p)" title="Remove publication" > Remove </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <modal-dialog v-model="addModal.open" title="Add a publication">
          <div class="space-y-3">
            <p class="text-sm text-gray-600">
              Paste <strong>PMID / PMCID / DOI / URL</strong>, preview it, then save.
            </p>

            <div class="flex gap-2">
              <input
                v-model="addModal.raw"
                class="w-full border rounded px-3 py-2"
                placeholder="e.g., PMID: 12345678 · PMCID: PMC1234567 · DOI: 10.1038/s41586-020-2649-2 · URL"
                @keydown.enter.prevent="doPreview"
              />
              <button class="btn" :disabled="addModal.loading || !addModal.raw" @click="doPreview">
                {{ addModal.loading ? 'Looking…' : 'Preview' }}
              </button>
            </div>

            <p v-if="addModal.error" class="text-sm text-red-600">{{ addModal.error }}</p>
            <div v-if="addModal.preview" class="rounded border p-3 space-y-1">
              <div class="font-medium">{{ addModal.preview.title || 'Untitled' }}</div>
              <div class="text-sm text-gray-600">
                {{ addModal.preview.journalTitle || '—' }} · {{ addModal.preview.pubType || '—' }} · {{ addModal.preview.date || addModal.preview.firstPublicationDate || '—' }}
              </div>
              <div class="text-xs text-gray-600">
                DOI: <span class="font-mono">{{ addModal.preview.doi || '—' }}</span>
                <span class="mx-2">|</span>
                PMID: <span class="font-mono">{{ addModal.preview.pmid || '—' }}</span>
                <span class="mx-2">|</span>
                PMCID: <span class="font-mono">{{ addModal.preview.pmcid || '—' }}</span>
              </div>
              <div class="flex items-center gap-2 pt-2">
                <a v-if="addModal.preview.url" :href="addModal.preview.url" target="_blank" rel="noopener" class="underline text-sm">Open</a>
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
                        <div><strong class="mr-1">SOURCE:</strong>{{ detailsModal.item?.source }}</div>
                        <div><strong class="mr-1">IDENTIFIER:</strong>{{ detailsModal.item?.identifier }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="btn transition-colors" :class="detailsModal.copyLabel === 'JSON copied!' ? 'bg-green-100 text-green-800' : ''" @click="copyMeta">{{ detailsModal.copyLabel }}</button>
                        <a v-if="detailsLink()" :href="detailsLink()" target="_blank" rel="noopener noreferrer" class="btn inline-flex items-center" title="Open link in new tab">
                            Open publication on new tab
                        </a>
                    </div>
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
