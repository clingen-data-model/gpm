<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/http'

const authStore = useAuthStore()
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
    await authStore.forceGetCurrentUser()

    console.log('post-attest coc:', authStore.currentUser?.person?.coc)
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
  <card :title="payload?.content?.definition?.title || 'ClinGen Code of Conduct'">
    <div v-if="loading" class="py-2">
      Loading…
    </div>

    <div v-else>
      <static-alert v-if="error" variant="danger" class="mb-3">
        {{ error }}
      </static-alert>
      
      <p class="text-center font-semibold whitespace-pre-line">
        {{ payload?.content?.definition?.subtitle }}
      </p>
      <p class="whitespace-pre-line">
        {{ payload?.content?.definition?.intro }}
      </p>
      <p>
        The full Code of Conduct can be found <a :href="payload?.content?.links?.full" target="_blank">here</a> and a one-page, 
        high-level summary of the Code can be found <a :href="payload?.content?.links?.summary" target="_blank">here</a>, 
        and a summary slide can be found <a :href="payload?.content?.links?.slide" target="_blank">here</a>.
      </p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="(section, idx) in payload?.content?.definition?.sections" :key="idx">
          <div class="coc-pill">
            {{ section.title }}
          </div>
          <ul class="mt-4 list-disc pl-6 space-y-2">
            <li v-for="(b, i) in section.bullets" :key="i">
              {{ b }}
            </li>
          </ul>
        </div>
      </div>
      <p>
        Have questions or want to report? Contact DAPC at <a href="mailto:dapc@clinicalgenome.org">dapc@clinicalgenome.org</a><br>
        Report anonymously at <a href="https://tinyurl.com/clingenreporting" target="_blank">https://tinyurl.com/clingenreporting</a>
      </p>
      <div class="mt-4">
        <checkbox v-model="agreed" label="I understand and will abide by the ClinGen Code of Conduct." />
      </div>

      <div class="mt-4 flex items-center space-x-2">
        <button type="button" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!agreed || loading" @click="submit">
          Submit
        </button>
      </div>
    </div>
  </card>
</template>
