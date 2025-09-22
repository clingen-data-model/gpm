<script setup>
import { ref, onMounted, watch, computed, onActivated, defineExpose } from "vue";
import { api } from "@/http";

const props = defineProps({ group: { type: Object, required: true } });

const loading = ref(false);
const items = ref([]);
const addOpen = ref(false);
const addText = ref("");
const posting = ref(false);
const error = ref("");
const groupUuid = computed(() => props.group?.uuid);

async function fetchPublications() {
    if (!groupUuid.value) return; else console.log("No group UUID; skipping fetch.");
    loading.value = true;
    error.value = "";
    try {
        const { data } = await api.get(`/api/groups/${groupUuid.value}/publications`, { });
        items.value = Array.isArray(data?.data) ? data.data : data;
    } catch (e) {
        error.value = "Failed to load publications.";
    } finally {
        loading.value = false;
    }
}

function refresh() { return fetchPublications(); }
defineExpose({ refresh });

function parseEntries(raw) {
    return raw
        .split(/[\n,]/)
        .map(s => s.trim())
        .filter(Boolean);
}

async function addPublications() {
    const entries = parseEntries(addText.value);
    if (!entries.length) return;

    posting.value = true;
    error.value = "";
    try {
        await api.post(`/api/groups/${groupUuid.value}/publications`, { entries });
        addOpen.value = false;
        addText.value = "";
        await fetchPublications();
    } catch (e) {
        error.value = e?.response?.data?.message || "Failed to add publications.";
    } finally {
        posting.value = false;
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

onMounted(() => {
    if (groupUuid.value) fetchPublications();
    else console.log("No group UUID; skipping fetch.");
});
watch(groupUuid, (val, old) => { if (val && val !== old) fetchPublications(); }, { immediate: true });
onActivated(() => {
    if (groupUuid.value && items.value.length === 0) fetchPublications();
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
    return p.meta?.url || p.url;
}
</script>

<template>
    <section class="space-y-3">
        <header class="flex items-center justify-between">
            <h3 class="text-xl font-semibold">Publications</h3>
            <div class="space-x-2">
                <button class="btn btn-primary btn-sm" @click="addOpen = true">Add publications</button>
                <button class="btn btn-sm" @click="fetchPublications" :disabled="loading">{{ loading ? 'Loading…' : 'Refresh' }}</button>
            </div>
        </header>

        <p v-if="error" class="text-red-600">{{ error }}</p>

        <div v-if="!loading && items.length === 0" class="text-gray-600">No publications added yet.</div>

        <div v-if="loading">Loading…</div>

        <div v-else class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Title</th>
                        <th class="px-3 py-2 text-left">Journal</th>
                        <th class="px-3 py-2 text-left">Identifiers</th>
                        <th class="px-3 py-2 text-left">Published</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in items" :key="p.id" class="border-t">
                        <td class="px-3 py-2">
                            <span class="inline-block px-2 py-0.5 rounded text-xs" :class="typeOf(p) === 'preprint' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'">{{ typeOf(p) }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div class="font-medium">
                                <a v-if="urlOf(p)" :href="urlOf(p)" target="_blank" class="underline">{{ titleOf(p) }}</a>
                                <span v-else>{{ titleOf(p) }}</span>
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
                            <span class="inline-block px-2 py-0.5 rounded text-xs" :class="{
                                    'bg-gray-100 text-gray-800': p.status === 'pending',
                                    'bg-green-100 text-green-800': p.status === 'enriched',
                                    'bg-red-100 text-red-800': p.status === 'failed'
                                }"
                            >
                                {{ p.status }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <button class="btn btn-xs" @click="removePublication(p)">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add modal -->
        <modal-dialog v-model="addOpen" title="Add Publications">
            <div class="space-y-2">
                <p class="text-sm text-gray-600">
                    Paste <strong>PMID / PMCID / DOI / URL</strong> —
                    one per line or comma-separated. Example: <code>PMID: 12345678</code>, <code>PMC1234567</code>, <code>10.1038/…</code>
                </p>
                <textarea v-model="addText" rows="8" class="w-full border rounded p-2"></textarea>
                <button-row submit-text="Add" :submitting="posting" @submitted="addPublications" @canceled="() => (addOpen = false)" />
            </div>
        </modal-dialog>
  </section>
</template>
