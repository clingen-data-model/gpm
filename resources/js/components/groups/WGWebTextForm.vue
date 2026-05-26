<script setup>
import { ref, computed, watch } from 'vue'
import { useStore } from 'vuex'
import { api, isValidationError } from '@/http'
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import { htmlFromMarkdown } from '@/markdown-utils'
import { hasAnyPermission } from '@/auth_utils'

const store = useStore()

const emit = defineEmits(['saved'])
const props = defineProps({ group: { type: Object, required: true } })

const excerpt = ref('')
const editing = ref(false)
const errors = ref({})
const saving = ref(false)
const htmlExcerpt = computed(() => htmlFromMarkdown(excerpt.value))

watch(() => props.group, g => { excerpt.value = g?.excerpt ?? '' }, { immediate: true })

function toggleEditing() {
  editing.value = !editing.value
}

async function save(e) {
  e?.preventDefault?.()
  if (!props.group?.uuid || saving.value) return

  errors.value = {}
  saving.value = true

  try {
    const response = await api.put(`/api/groups/${props.group.uuid}/excerpt`, {
      excerpt: excerpt.value,
    })
    const updatedGroup = response.data?.data ?? response.data
    props.group.excerpt = updatedGroup?.excerpt ?? excerpt.value
    excerpt.value = props.group.excerpt ?? ''
    emit('saved', updatedGroup)
    store.commit('pushSuccess', 'Excerpt updated.')
    editing.value = false
  } catch (err) {
    if (isValidationError(err)) {
      errors.value = err.response?.data?.errors || {}
    } else {
      store.commit('pushError', err?.response?.data?.message || 'Failed to update excerpt.')
    }
  } finally {
    saving.value = false
  }
}

function cancelEdit(e) {
  e?.preventDefault?.()
  editing.value = false
  errors.value = {}
  excerpt.value = props.group?.excerpt ?? ''
}
</script>

<template>
  <div>
    <header class="flex justify-between items-center">
      <h4>Working Group Excerpt</h4>
      <EditIconButton
        v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
        @click="toggleEditing"
      />
    </header>
    <div class="mt-2">
      <p class="text-sm">
        This excerpt is used for public display on the website. 
        It appears in the blue content heading section on the Working Group page.
      </p>
      <transition name="fade" mode="out-in">
        <div v-if="editing">
          <RichTextEditor v-model="excerpt" />
        </div>
        <div v-else class="border-2 p-4 rounded">
          <div
            v-if="excerpt"
            class="markdown-preview"
            v-html="htmlExcerpt"
          />

          <p v-else class="well cursor-pointer">A Working Group excerpt has not yet been provided.</p>
        </div>
      </transition>

      <button-row v-if="editing">
        <button type="button" class="btn white" @click="cancelEdit">Cancel</button>
        <button type="button" class="btn blue" :disabled="saving || !group?.uuid" @click="save">
          <span v-if="saving">Saving…</span>
          <span v-else>Save</span>
        </button>
      </button-row>
    </div>
  </div>
</template>

<style scoped>
.markdown-preview :deep(p) {
  margin: 0.5rem 0;
}

.markdown-preview :deep(ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin: 0.5rem 0;
}

.markdown-preview :deep(ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin: 0.5rem 0;
}

.markdown-preview :deep(li) {
  display: list-item;
  margin: 0.25rem 0;
}

.markdown-preview :deep(strong) {
  font-weight: 600;
}

.markdown-preview :deep(em) {
  font-style: italic;
}
</style>