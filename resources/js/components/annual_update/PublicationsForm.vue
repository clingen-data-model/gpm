<script setup>
import { api } from"@/http";
import { debounce, cloneDeep } from 'lodash-es'
import { computed, ref, watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
  version: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  groupUuid: { type: String, default: null },
  shapeExchange: { type: Boolean, default: true } // Flag query string that it's coming from Annual Updates
})

const emit = defineEmits(['update:modelValue'])

const formatDateParam = (value) => {
  if (!value) return null
  return String(value).slice(0, 10)
}

const au = computed(() => props.modelValue || {})
const year = computed(() => Number(props.version.for_year))
const isComplete = computed(() => Boolean(au.value?.completed_at))
const groupUuid = computed(() => {
  return ( props.groupUuid || au.value?.expert_panel?.group?.uuid || (typeof window !== 'undefined' && window?.appRouteParams?.uuid) || null )
})

const pubs = ref([])
const loading = ref(false)
const note = ref('')
const loadError = ref('')
const publicationKey = p => p?.publication_id
const sourceLabel = p => String(p?.source_type || '').toUpperCase()
const sourceValue = p => p?.source_id
const publishedOf = p => formatDateParam(p?.published_at)
const publicationPeriodStart = computed(() => `${year.value}-01-01`)
const publicationPeriodEnd = computed(() => `${year.value + 1}-03-31`)
const inPeriod = p => publishedOf(p) >= publicationPeriodStart.value && publishedOf(p) <= publicationPeriodEnd.value
const totalCount = computed(() => pubs.value.length)
const includedCount = computed(() => pubs.value.filter(p => p.included).length)
const isMissing = p => !!p._missing
const isNew = p => !!p._new
const emitChange = debounce(() => {
  if (isComplete.value) return
  const next = cloneDeep(au.value)
  next.data = next.data || {}
  next.data.publications = pubs.value.filter(p => p.included).map(p => ({ publication_id: publicationKey(p) }))
  next.data.publications_note = note.value
  emit('update:modelValue', next)
}, 600)
onBeforeUnmount(() => { generation++; emitChange.cancel() })
defineExpose({ flush: () => emitChange.flush() })

let generation = 0
const preloadPublications = async () => {
  if (isComplete.value || !au.value.id || !groupUuid.value) return
  const current = generation
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await api.get(`/api/groups/${groupUuid.value}/publications`, { params: { shape_exchange: true } })
    if (current !== generation || isComplete.value) return
    const saved = new Map(pubs.value.map(p => [publicationKey(p), p]))
    const latest = new Map(data.map(p => [publicationKey(p), {
      ...p, included: saved.get(publicationKey(p))?.included ?? false,
      _new: !saved.has(publicationKey(p)),
    }]))
    for (const [key, p] of saved) {
      if (p.included && !latest.has(key)) latest.set(key, { ...p, _missing: true })
    }
    pubs.value = [...latest.values()].sort((a, b) =>
      (publishedOf(b) || '').localeCompare(publishedOf(a) || '') || String(a.title || '').localeCompare(String(b.title || '')))
  } catch (e) {
    if (current === generation) loadError.value = 'Unable to load publications. Retry using Refresh list.'
  } finally {
    if (current === generation) loading.value = false
  }
}
watch(() => [au.value.id, groupUuid.value, isComplete.value], () => {
  generation++
  loading.value = false
  loadError.value = ''
  emitChange.cancel()
  pubs.value = cloneDeep(au.value.data?.publications || []).map(p => ({ ...p, included: p.included ?? true }))
  note.value = au.value.data?.publications_note || ''
  if (!isComplete.value) preloadPublications()
}, { immediate: true })
const refresh = () => { if (!loading.value) return preloadPublications() }
const toggleAll = include => {
  if (isComplete.value) return
  pubs.value = pubs.value.map(p => ({ ...p, included: include ? (p._missing ? p.included : true) : false }))
  emitChange()
}
const onToggleRow = (idx, value) => {
  if (isComplete.value) return
  pubs.value[idx].included = value
  emitChange()
}
const removeRow = idx => {
  if (isComplete.value) return
  pubs.value.splice(idx, 1)
  emitChange()
}
const onNoteInput = () => { if (!isComplete.value) emitChange() }
const groupPublicationsUrl = computed(() =>
  groupUuid.value ? `/groups/${groupUuid.value}?tab=publications` : null
)
</script>

<template>
  <div class="space-y-4">
    <div class="rounded-md border-l-4 border-blue-300 bg-blue-50 px-3 py-2 text-blue-900">
      To add new publications, go to your Group's page and open the <strong>Publications</strong> tab.
      <template v-if="groupPublicationsUrl">
        You can open it here:
        <a :href="groupPublicationsUrl" class="underline hover:no-underline font-medium" target="_blank" rel="noopener">Group Detail page &gt; Publications</a>.
      </template>
      <template v-else>
        (Use the gray breadcrumb at the top: <em>Group &gt; EP</em>.)
      </template>
      <template v-if="isComplete">
        <span class="ml-2 italic">(This Annual Update is read-only.)</span>
      </template>
    </div>

    <div class="flex items-center justify-between">
      <div class="text-gray-700" v-if="totalCount > 0">
        Included <strong>{{ includedCount }}</strong> of <strong>{{ totalCount }}</strong>
      </div>

      <div class="flex items-center gap-2">
        <button type="button" class="btn sm" @click="refresh" :disabled="isComplete || loading">Refresh list</button>
        <button type="button" class="btn sm" @click="toggleAll(true)" :disabled="isComplete || loading || totalCount === 0">Include all</button>
        <button type="button" class="btn sm" @click="toggleAll(false)" :disabled="isComplete || loading || totalCount === 0">Exclude all</button>
      </div>
    </div>

    <p v-if="loadError" class="text-red-600">{{ loadError }}</p>
    <p v-if="errors?.publications" class="text-red-600">{{ errors.publications }}</p>
    <p v-if="pubs.some(p => p._missing && p.included)" class="text-amber-700">Some selected publications are unavailable. Remove these selections before submitting.</p>
    <div v-if="loading" class="text-gray-500">Loading publications...</div>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full border border-gray-200 rounded-md">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left font-medium">Include</th>
            <th class="px-3 py-2 text-left font-medium">Identifier</th>
            <th class="px-3 py-2 text-left font-medium">Title / Journal</th>
            <th class="px-3 py-2 font-medium">Published</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(p, idx) in pubs" :key="publicationKey(p)" class="border-t">
            <td class="px-3 py-2">
              <input
                type="checkbox"
                :checked="!!p.included"
                :disabled="isComplete"
                @change="onToggleRow(idx, $event.target.checked)"
              />
            </td>
            <td class="px-3 py-2">
              <div class="">
                <template v-if="sourceLabel(p) && sourceValue(p)">
                  <span class="font-mono">{{ sourceLabel(p) }}:</span>
                  <span class="font-mono">{{ sourceValue(p) }}</span>
                </template>
                <template v-else>
                  <span class="text-gray-500 italic">No ID</span>
                </template>
              </div>
            </td>
            <td class="px-3 py-2">
              <div class="font-medium leading-snug line-clamp-2">
                {{ p.title || p.publication_id }}
                <span v-if="isNew(p)" class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-800 align-middle">New</span>
                <span v-if="isMissing(p)" class="ml-2 text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 align-middle">Unavailable selection</span>
                <button
                  v-if="isMissing(p)"
                  type="button"
                  class="ml-2 text-[10px] px-1.5 py-0.5 rounded border"
                  :disabled="isComplete"
                  @click="removeRow(idx)"
                >
                  Remove
                </button>
              </div>
              <div>
                <div v-if="p.preprint" class="text-xs mt-1">
                  Linked preprint: {{ p.preprint.title }} ? {{ sourceLabel(p.preprint) }}: {{ sourceValue(p.preprint) }}
                  <span v-if="publishedOf(p.preprint)"> ? {{ formatDate(publishedOf(p.preprint)) }}</span>
                </div>
                <div v-if="p.authors && p.authors.length > 0"><span class="font-medium">Authors:</span> {{ p.authors.join(', ') }}</div>                
                <span class="font-medium">Publication Type:</span> {{ p.type ?? 'n.a' }}  · 
                <span class="font-medium">Journal:</span> {{ p.journal ?? 'n.a' }}
              </div>
            </td>
            <td class="px-3 py-2" :class="inPeriod(p) ? 'text-blue-700' : 'text-gray-500'">
              {{ formatDate(publishedOf(p)) || 'n.a' }}
            </td>
          </tr>
        </tbody>
      </table>
      <div class="text-gray-500">
        All publication dates are selectable. Blue dates fall within this Annual Update period {{ formatDate(publicationPeriodStart) }} to {{ formatDate(publicationPeriodEnd) }}; grey dates fall outside it.
      </div>
    </div>

    <div class="space-y-1">
      <label class="block font-medium">Notes about publications (optional)</label>

      <textarea
        class="w-full rounded-md border p-2"
        rows="3"
        placeholder="Add any context or comments related to this year's publications..."
        v-model="note"
        @input="onNoteInput"
        :disabled="isComplete"
      ></textarea>
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