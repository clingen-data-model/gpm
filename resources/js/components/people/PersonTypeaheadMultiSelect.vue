<script setup>
import { ref, watch, computed, onBeforeUnmount, onMounted } from 'vue'
import api from '@/http/api'

const props = defineProps({
  expertPanelUuid: { type: String, required: true },
  modelValue: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: 'Search people…' },
  peopleById: { type: Object, default: () => ({}) },
})

const emit = defineEmits([
  'update:modelValue',
  'selected',
  'results',
])

const q = ref('')
const loading = ref(false)
const open = ref(false)
const options = ref([])
const selectedCache = ref({})
let t = null

const selectedIds = computed(() => (props.modelValue || []).map(Number))
watch(() => props.peopleById, (map) => {
    if (!map) return
    for (const [id, p] of Object.entries(map)) {
      if (!p) continue
      cachePerson({ ...p, id: Number(id) })
    }
  },
  { immediate: true, deep: true }
)

watch(selectedIds, (ids) => {
    for (const id of ids) {
      if (selectedCache.value[id]) continue
      const p = props.peopleById?.[id]
      if (p) cachePerson(p)
    }
  },
  { immediate: true }
)

function displayName(p) {
  return (
    p?.name ||
    p?.full_name ||
    [p?.first_name, p?.last_name].filter(Boolean).join(' ') ||
    null
  )
}

function cachePerson(p) {
  if (!p?.id) return
  const id = Number(p.id)
  if (!Number.isFinite(id)) return
  selectedCache.value[id] = {
    ...p,
    id,
    name: displayName(p) || selectedCache.value[id]?.name || null,
  }
}

function cachePeople(list) {
  if (!Array.isArray(list)) return
  list.forEach(cachePerson)
}

function addPerson(p) {
  if (!p?.id) return
  cachePerson(p)
  emit('selected', selectedCache.value[Number(p.id)])

  const id = Number(p.id)
  if (selectedIds.value.includes(id)) {
    q.value = ''
    open.value = false
    return
  }

  emit('update:modelValue', [...selectedIds.value, id])
  q.value = ''
  open.value = false
}

function removeId(id) {
  const next = selectedIds.value.filter(x => Number(x) !== Number(id))
  emit('update:modelValue', next)
}

async function fetchOptions() {
  loading.value = true
  try {
    const res = await api.get(
      `/api/applications/${props.expertPanelUuid}/funding-awards/pi-options`,
      { params: { q: q.value, limit: 25 } }
    )
    options.value = Array.isArray(res.data) ? res.data : []
    cachePeople(options.value)
    emit('results', options.value)
  } finally {
    loading.value = false
  }
}

watch(q, () => {
  if (t) clearTimeout(t)
  if (!q.value || q.value.trim().length < 2) {
    options.value = []
    return
  }
  t = setTimeout(fetchOptions, 250)
})

onBeforeUnmount(() => {
  if (t) clearTimeout(t)
})

const root = ref(null)

function close() {
  open.value = false
  q.value = ''
  options.value = []
}

function onDocClick(e) {
  if (!open.value) return
  if (!root.value) return
  if (root.value.contains(e.target)) return
  close()
}

function onKeydown(e) {
  if (e.key === 'Escape') close()
}

onMounted(() => {
  document.addEventListener('mousedown', onDocClick)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocClick)
  document.removeEventListener('keydown', onKeydown)
  if (t) clearTimeout(t)
})

</script>

<template>
  <div class="relative" ref="root">
    <!-- chips -->
    <div v-if="selectedIds.length" class="flex flex-wrap gap-2 mb-2">
      <span
        v-for="id in selectedIds"
        :key="id"
        class="inline-flex items-center gap-2 px-2 py-1 border rounded text-sm"
      >
        {{ props.peopleById?.[id]?.name || selectedCache[id]?.name || `Person #${id}` }}
        <button
          type="button"
          class="link"
          @click="removeId(id)"
          :disabled="disabled"
          aria-label="Remove"
        >
          ×
        </button>
      </span>
    </div>

    <input
      v-model="q"
      class="w-full"
      type="text"
      :placeholder="placeholder"
      :disabled="disabled"
      @focus="open = true"
    />

    <div v-if="open && (loading || (q && q.trim().length >= 2))" class="absolute z-50 mt-1 w-full bg-white border rounded shadow">
      <div v-if="loading" class="p-2 text-sm text-gray-600">Searching…</div>

      <div v-else-if="!q || q.trim().length < 2" class="p-2 text-sm text-gray-600">
        Type 2+ characters to search.
      </div>

      <div v-else-if="options.length === 0" class="p-2 text-sm text-gray-600">
        No matches.
      </div>

      <button
        v-for="p in options"
        :key="p.id"
        type="button"
        class="w-full text-left px-3 py-2 hover:bg-gray-100"
        @mousedown.prevent
        @click="addPerson(p)"
      >
        <div class="flex justify-between">
          <span :class="[p.in_group ? 'font-semibold' : '',]">{{ displayName(p) || `Person #${p.id}` }}</span>
          <span class="flex items-center gap-2">
            <span v-if="p.is_group_member" class="text-xs px-2 py-0.5 border rounded">
              Group
            </span>
            <span v-if="p.email" class="text-xs text-gray-500">{{ p.email }}</span>
          </span>
        </div>
        <div v-if="p.is_group_member" class="text-xs text-gray-500">
          Group member
        </div>
      </button>
    </div>
  </div>
</template>
