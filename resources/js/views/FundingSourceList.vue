<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/http/api'
import SubmissionWrapper from '@/components/groups/SubmissionWrapper.vue'

const MAX_FILE_BYTES = 3 * 1024 * 1024
const MAX_DIM = 600
const CAPTION_MAX = 500
const CAPTION_PREVIEW = 100

const loading = ref(false)
const items = ref([])
const fundingTypes = ref([])
const filterString = ref('')
const showForm = ref(false)
const editing = ref(null)

const form = reactive({
  name: '',
  funding_type_id: '',
  caption: '',
  website_url: '',
  logo_path: '',
})

const logoFile = ref(null)
const logoPreviewUrl = ref('')
const logoError = ref('')
const removeLogo = ref(false)

const expandedCaptionIds = reactive(new Set())

const sort = ref({ field: 'id', desc: false })
const fields = [
  { name: 'logo', label: '', sortable: false },
  { name: 'id', label: 'ID', sortable: true },
  { name: 'name', label: 'Name', sortable: true },
  { name: 'type', label: 'Type', sortable: true },
  { name: 'caption', label: 'Caption', sortable: false },
  { name: 'website_url', label: 'Website', sortable: false },
  { name: 'actions', label: '', sortable: false },
]

const filteredItems = computed(() => {
  if (!filterString.value) return items.value
  const pattern = new RegExp(`.*${filterString.value}.*`, 'i')
  return items.value.filter(i => {
    const typeName = getTypeName(i)
    return (i.name && i.name.match(pattern)) || (typeName && typeName.match(pattern)) ||(i.caption && stripHtml(i.caption).match(pattern))
  })
})

function stripHtml(text) {
  return (text ?? '').replace(/<[^>]*>/g, '')
}

function sanitizedCaption(text) {
  const plain = stripHtml(text)
  return plain.length > CAPTION_MAX ? plain.slice(0, CAPTION_MAX) : plain
}

function getTypeName(item) {
  return item?.funding_type?.name || item?.funding_type_name || item?.type || ''
}

function resetForm() {
  form.name = ''
  form.funding_type_id = ''
  form.caption = ''
  form.website_url = ''
  form.logo_path = ''
  removeLogo.value = false
  clearLogoSelection(true)
}

function clearLogoSelection(full = false) {
  logoError.value = ''
  logoFile.value = null

  if (full) {
    if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
      URL.revokeObjectURL(logoPreviewUrl.value)
    }
    logoPreviewUrl.value = ''
    return
  }

  if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreviewUrl.value)
    logoPreviewUrl.value = ''
  }
}

async function fetchFundingTypes() {
  const res = await api.get('/api/funding-types')
  fundingTypes.value = Array.isArray(res.data) ? res.data : []
}

async function fetchItems() {
  loading.value = true
  try {
    const res = await api.get('/api/funding-sources')
    items.value = Array.isArray(res.data) ? res.data : []
  } finally {
    loading.value = false
  }
}

function startCreate() {
  editing.value = null
  resetForm()
  showForm.value = true
}

function startEdit(item) {
  editing.value = item
  form.name = item.name ?? ''
  form.funding_type_id = item.funding_type_id ?? item.funding_type?.id ?? ''
  form.caption = item.caption ?? ''
  form.website_url = item.website_url ?? ''
  form.logo_path = item.logo_path ?? ''

  removeLogo.value = false
  clearLogoSelection(true)

  logoPreviewUrl.value = item.logo_url || ''
  showForm.value = true
}

function toggleCaption(item) {
  if (!item?.id) return
  if (expandedCaptionIds.has(item.id)) expandedCaptionIds.delete(item.id)
  else expandedCaptionIds.add(item.id)
}

function captionPreview(item) {
  const txt = stripHtml(item?.caption ?? '')
  if (expandedCaptionIds.has(item?.id)) return txt
  if (txt.length <= CAPTION_PREVIEW) return txt
  return txt.slice(0, CAPTION_PREVIEW) + '…'
}

function captionHasMore(item) {
  const txt = stripHtml(item?.caption ?? '')
  return txt.length > CAPTION_PREVIEW
}

function getLogoSrc(item) {
  return item?.logo_url || ''
}

async function validateAndSetLogo(file) {
  logoError.value = ''
  if (!file) return

  const allowed = ['image/png', 'image/jpeg', 'image/gif']
  if (!allowed.includes(file.type)) {
    logoError.value = 'Invalid file type. Allowed: PNG, JPG/JPEG, GIF.'
    return
  }

  if (file.size > MAX_FILE_BYTES) {
    logoError.value = 'File is too large. Max 3 MB.'
    return
  }

  const tmpUrl = URL.createObjectURL(file)
  try {
    const img = new Image()
    await new Promise((resolve, reject) => {
      img.onload = resolve
      img.onerror = reject
      img.src = tmpUrl
    })

    if (img.width > MAX_DIM || img.height > MAX_DIM) {
      logoError.value = `Image too large. Max ${MAX_DIM}×${MAX_DIM}px.`
      return
    }

    logoFile.value = file
    removeLogo.value = false

    // replace preview
    if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
      URL.revokeObjectURL(logoPreviewUrl.value)
    }
    logoPreviewUrl.value = tmpUrl
  } catch (e) {
    logoError.value = 'Could not read image file.'
  } finally {
    if (!logoFile.value || logoPreviewUrl.value !== tmpUrl) {
      URL.revokeObjectURL(tmpUrl)
    }
  }
}

async function onLogoChange(e) {
  const file = e?.target?.files?.[0] || null
  await validateAndSetLogo(file)
}

function onToggleRemoveLogo() {
  if (removeLogo.value) {
    logoFile.value = null
    logoError.value = ''

    if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
      URL.revokeObjectURL(logoPreviewUrl.value)
    }
    logoPreviewUrl.value = ''
  } else {
    if (editing.value) {
      logoPreviewUrl.value = editing.value.logo_url || ''
    }
  }
}

async function save() {
  if (!form.funding_type_id) {
    alert('Funding Type is required.')
    return
  }

  form.caption = sanitizedCaption(form.caption)

  const fd = new FormData()
  fd.append('name', form.name ?? '')
  fd.append('funding_type_id', String(form.funding_type_id))
  fd.append('caption', form.caption ?? '')
  fd.append('website_url', form.website_url ?? '')

  if (editing.value && removeLogo.value) {
    fd.append('remove_logo', '1')
  }

  if (logoFile.value) {
    fd.append('logo', logoFile.value)
  }

  if (editing.value) {
    await api.post(`/api/funding-sources/${editing.value.id}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  } else {
    await api.post('/api/funding-sources', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  }

  showForm.value = false
  await fetchItems()
}

async function destroy(item) {
  if (!confirm(`Delete funding source "${item.name}"?`)) return
  await api.delete(`/api/funding-sources/${item.id}`)
  await fetchItems()
}

onMounted(async () => {
  await fetchFundingTypes()
  await fetchItems()
})
</script>

<template>
  <div>
    <h1 class="flex justify-between items-center">
      Funding Sources
      <button
        v-if="hasRole('super-user') || hasRole('super-admin')"
        class="btn btn-xs"
        @click="startCreate"
      >
        Add Funding Source
      </button>
    </h1>

    <div class="mb-2">
      Filter:
      <input v-model="filterString" type="text" placeholder="name,type,caption">
    </div>

    <div v-if="loading" class="text-center w-full">Loading...</div>

    <data-table
      v-else
      v-model:sort="sort"
      v-remaining-height
      :data="filteredItems"
      :fields="fields"
      row-class="active:bg-blue-100"
    >
      <template #cell-logo="{ item }">
        <img
          v-if="getLogoSrc(item)"
          :src="getLogoSrc(item)"
          :width="50"
          :height="50"
          class="object-contain"
          style="max-width: 50px; max-height: 50px;"
        />
      </template>

      <template #cell-type="{ item }">
        {{ getTypeName(item) }}
      </template>

      <template #cell-caption="{ item }">
        <span>{{ captionPreview(item) }}</span>
        <button
          v-if="captionHasMore(item)"
          class="link ml-2"
          @click.stop="toggleCaption(item)"
        >
          {{ expandedCaptionIds.has(item.id) ? 'Less' : 'More' }}
        </button>
      </template>

      <template #cell-actions="{ item }">
        <div v-if="hasRole('super-user') || hasRole('super-admin')" class="flex items-center gap-2 whitespace-nowrap">
          <button class="btn btn-xs" @click.stop="startEdit(item)">Edit</button>
          <button class="btn btn-xs" @click.stop="destroy(item)">Delete</button>
        </div>
      </template>
    </data-table>

    <modal-dialog v-model="showForm" :title="editing ? 'Edit Funding Source' : 'Add Funding Source'" size="md">
      <SubmissionWrapper @submitted="save" @canceled="showForm = false">
        <div class="space-y-2">
          <div>
            <label class="block text-sm">Name</label>
            <input v-model="form.name" class="w-full" type="text">
          </div>

          <div>
            <label class="block text-sm">Funding Type</label>
            <select v-model="form.funding_type_id" class="w-full">
              <option value="" disabled>Select a type…</option>
              <option v-for="t in fundingTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm">Caption (max 500, text only)</label>
            <textarea v-model="form.caption" class="w-full" :maxlength="500" rows="3" placeholder="(optional)" />
            <div class="text-xs text-gray-600">{{ sanitizedCaption(form.caption).length }}/500</div>
          </div>

          <div>
            <label class="block text-sm">Website URL</label>
            <input v-model="form.website_url" class="w-full" type="text" placeholder="(optional)">
          </div>

          <div>
            <label class="block text-sm">Logo (PNG/JPG/GIF, max 600×600, max 3MB)</label>

            <div v-if="editing" class="mb-2 flex items-center gap-2">
              <input id="removeLogo" type="checkbox" v-model="removeLogo" @change="onToggleRemoveLogo" />
              <label for="removeLogo" class="text-sm">Remove existing logo</label>
            </div>

            <input type="file" accept="image/png,image/jpeg,image/gif" @change="onLogoChange" :disabled="removeLogo" />

            <div v-if="logoError" class="text-sm text-red-600 mt-1"> {{ logoError }}</div>

            <div v-if="logoPreviewUrl" class="mt-2">
              <img :src="logoPreviewUrl" class="object-contain border" style="max-width: 150px; max-height: 150px;" />
              <div class="mt-1"><button class="btn btn-xs" type="button" @click="clearLogoSelection(true)">Clear Selected File</button></div>
            </div>
          </div>
        </div>
      </SubmissionWrapper>
    </modal-dialog>
  </div>
</template>
