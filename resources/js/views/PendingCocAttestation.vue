<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/http'

const store = useStore()
const router = useRouter()
const route = useRoute()

const loading = ref(false)
const agreed = ref(false)
const payload = ref(null)
const error = ref(null)

const status = computed(() => payload.value?.status?.status ?? 'missing')
const isDue = computed(() => ['missing','expired','version_mismatch'].includes(status.value))

const fetchCoc = async () => {
  loading.value = true
  error.value = null
  try {
    payload.value = await api.get('/api/people/coc').then(r => r.data)
  } catch (e) {
    console.error(e)
    error.value = 'Could not load Code of Conduct.'
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  if (!agreed.value) return
  loading.value = true
  error.value = null
  try {
    await api.post('/api/people/coc/attest', { agreed: true })
    await store.dispatch('forceGetCurrentUser')

    console.log('post-attest coc:', store.getters.currentUser?.person?.coc)
    console.log('redirect query:', route.query.redirect)

    const redirect = route.query.redirect
    if (typeof redirect === 'string' && redirect.length > 0) {
      return router.replace(redirect)
    }
    return router.push({ name: 'Dashboard' })
  } catch (e) {
    console.error(e)
    error.value = 'Unable to save your attestation. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchCoc)
</script>

<template>
  <card title="Code of Conduct Attestation">
    <div v-if="loading" class="py-2">Loading…</div>

    <div v-else>
      <static-alert v-if="error" variant="danger" class="mb-3">
        {{ error }}
      </static-alert>

      <!-- Abbreviated CoC text -->
      <div class="prose max-w-none whitespace-pre-wrap border border-gray-200 rounded p-3 bg-white">
        {{ payload?.content?.content }}
      </div>

      <div class="mt-4">
        <checkbox v-model="agreed" label="I have read and agree" />
      </div>

      <div class="mt-4 flex items-center space-x-2">
        <button class="btn btn-primary" :disabled="!agreed || loading" @click="submit">
          Submit
        </button>
      </div>
    </div>
  </card>
</template>
