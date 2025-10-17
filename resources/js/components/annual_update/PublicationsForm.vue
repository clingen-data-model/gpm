<script setup>
import { api } from "@/http";
import { debounce, cloneDeep } from 'lodash-es'
import { computed, onMounted, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
  version: { type: [String, Number], required: true },
  errors: { type: Object, default: () => ({}) },
  groupUuid: { type: String, default: null },
  shapeExchange: { type: Boolean, default: true }
})

const emit = defineEmits(['update:modelValue'])

const au = computed(() => props.modelValue || {})
const year = computed(() => Number(props.version))
const isComplete = computed(() => Boolean(au.value?.completed_at))
const groupUuid = computed(() => {
    return ( props.groupUuid || au.value?.expert_panel?.group?.uuid || (typeof window !== 'undefined' && window?.appRouteParams?.uuid) || null )
})

const pubs = ref([]);
const loading = ref(false);
const note = ref('');
const totalCount = computed(() => Number(pubs.value.length) || 0)
const includedCount = computed(() => pubs.value.filter(p => !!p.included).length)

const normalizePublication = (pub) => {
    const isExchange = pub && typeof pub === 'object' && 'uuid' in pub && 'identifiers' in pub && 'published' in pub
    if (isExchange) {
        return {
            uuid: pub.uuid,
            type: pub.type ?? null,
            title: pub.title ?? '',
            authors: Array.isArray(pub.authors) ? pub.authors : [],
            journal: pub.journal ?? '',
            identifiers: pub.identifiers ?? {},
            published: pub.published ?? null,
            url: pub.url ?? null,
            included: pub.included ?? true
        }
    }
  
    return {
        uuid: pub.uuid ?? pub.id ?? null,
        type: pub.pub_type ?? pub.type ?? null,
        title: pub.title ?? '',
        authors: Array.isArray(pub.authors) ? pub.authors : [],
        journal: pub.journal ?? '',
        identifiers: {
            doi: pub.doi ?? pub.identifiers?.doi ?? null,
            pmid: pub.pmid ?? pub.identifiers?.pmid ?? null,
            pmcid: pub.pmcid ?? pub.identifiers?.pmcid ?? null,
        },
        published: (pub.published_at ?? pub.published ?? null)?.toString().slice(0, 10),
        url: pub.url ?? null,
        included: pub.included ?? true
    }
}

const emitChange = debounce(() => {
    if (isComplete.value) return
    const next = cloneDeep(au.value) || {}
    next.data = next.data || {}
    next.data.publications = cloneDeep(pubs.value)
    next.data.publications_note = note.value ?? ''
    emit('update:modelValue', next)
}, 600)

watch(() => props.modelValue, (val) => {
        if (!val) return
        const d = val.data || {}
        if (Array.isArray(d.publications) && d.publications.length > 0) {
            pubs.value = cloneDeep(d.publications)
        }
        if (typeof d.publications_note === 'string') note.value = d.publications_note
    }, { deep: true, immediate: true }
)

const preloadPublications = async () => {
    console.log('preload guard', { year: year.value, groupUuid: groupUuid.value });
    if (!year.value || !groupUuid.value) { 
        console.log("Return no UUID || year")
        console.log('preload: missing prereqs → returning early');
        return
    }
    console.log('preload: prereqs OK → fetching…');
    loading.value = true
    const params = { status: 'enriched', start: `${year.value}-01-01`, end:   `${year.value}-12-31` }
    if (props.shapeExchange) params.shape_exchange = 1

    try {
        console.log("Send request publication UUID: " + groupUuid.value)
        const { data } = await api.get(`/api/groups/${groupUuid.value}/publications`, { params })
        const normalized = (Array.isArray(data) ? data : []).map(normalizePublication)
        console.log("normalized: ", normalized)
        const seen = new Set()
        const unique = []
        for (const p of normalized) {
            if (!p.uuid || seen.has(p.uuid)) continue
            seen.add(p.uuid)
            unique.push({ ...p, included: false })
        }
        console.log("unique: ", unique)
        
        if (!Array.isArray(au.value?.data?.publications) || !au.value.data.publications.length) {
            pubs.value = unique
            emitChange()
        }
        console.log("pubs: ", pubs.value.length)
    } catch (e) {    
    } finally {
        loading.value = false
    }
}

const preloaded = ref(false)
watch(() => [groupUuid.value, year.value, au.value?.data?.publications?.length],
    async ([uuid, yr, len]) => {
    if (!uuid || !yr) return

    const parentHas = Array.isArray(au.value?.data?.publications) && au.value.data.publications.length > 0
    if (parentHas || pubs.value.length > 0 || preloaded.value) return
        preloaded.value = true
        await preloadPublications()
    }, { immediate: true }
)

onMounted(() => { })

const toggleAll = (include) => {
    if (isComplete.value) return
    pubs.value = pubs.value.map(p => ({ ...p, included: !!include }))
    emitChange()
}
const onToggleRow = (idx, value) => {
    if (isComplete.value) return
    pubs.value = pubs.value.map((p, i) => i === idx ? { ...p, included: !!value } : p);
    emitChange()
}
const onNoteInput = () => { if (! isComplete.value) emitChange() }
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-lg font-semibold">Publications from {{ year }}</h4>
                <p class="text-sm text-gray-600">
                These are your group’s publications with status <em>enriched</em> in {{ year }}. Check which to include.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="btn sm" @click="toggleAll(true)" :disabled="isComplete || loading || totalCount === 0">Include all</button>
                <button type="button" class="btn sm" @click="toggleAll(false)" :disabled="isComplete || loading || totalCount === 0">Exclude all</button>
            </div>
        </div>

        <div class="text-sm text-gray-700" v-if="totalCount > 0">
            Included <strong>{{ includedCount }}</strong> of <strong>{{ totalCount }}</strong>
        </div>

        <div v-if="loading" class="text-sm text-gray-500">Loading publications...</div>

        <div v-else-if="totalCount === 0" class="rounded-md border border-dashed p-4 text-sm text-gray-600">
            No enriched publications found for {{ year }}.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 rounded-md">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Include</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Identifier</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Title / Journal</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Published</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(p, idx) in pubs" :key="p.uuid" class="border-t">
                        <td class="px-3 py-2">
                            <input type="checkbox" :checked="!!p.included" :disabled="isComplete" @change="onToggleRow(idx, $event.target.checked)" />
                        </td>
                        <td class="px-3 py-2">
                            <div class="text-sm">
                                <template v-if="p.identifiers?.pmid">
                                    <span class="font-mono">PMID:</span> <span class="font-mono break-all">{{ p.identifiers.pmid }}</span>
                                </template>
                                <template v-else-if="p.identifiers?.pmcid">
                                    <span class="font-mono">PMCID:</span> <span class="font-mono break-all">{{ p.identifiers.pmcid }}</span>
                                </template>
                                <template v-else-if="p.identifiers?.doi">
                                    <span class="font-mono">DOI:</span> <span class="font-mono break-all">{{ p.identifiers.doi }}</span>
                                </template>
                                <template v-else>
                                    <span class="text-gray-500 italic">No ID</span>
                                </template>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <div class="text-sm font-medium leading-snug line-clamp-2">{{ p.title }}</div>
                            <div class="text-xs text-gray-600">
                                <span v-if="p.type">{{ p.type }}</span>
                                <span v-if="p.type && p.journal"> · </span>
                                <span v-if="p.journal">{{ p.journal }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-sm">
                        {{ p.published || '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    <!-- Section-level note -->
    <div class="space-y-1">
        <label class="block text-sm font-medium">Notes about publications (optional)</label>
        
      <textarea class="w-full rounded-md border p-2 text-sm" rows="3" placeholder="Add any context or comments related to this year’s publications..." v-model="note" @input="onNoteInput" :disabled="isComplete"></textarea>
      <div v-if="errors?.publications_note" class="text-xs text-red-600">
        {{ errors.publications_note }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn { display:inline-flex; align-items:center; justify-content:center; border:1px solid #e5e7eb; padding:.375rem .75rem; border-radius:.5rem; font-size:.75rem; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.btn.sm { padding:.25rem .5rem; font-size:.7rem; }
.line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
</style>
