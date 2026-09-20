<script setup>
import { onMounted, ref } from 'vue'
import { api } from '@/http'
import { setFakeIdpToken } from '@/idp/fake_idp'

/**
 * Stand-in for the identity provider's hosted sign-in when IDP_DRIVER=fake.
 * Picks (or types) an email, gets a fake session token from the dev endpoint
 * and hands control back to the login page, which performs the normal
 * session exchange.
 */
const emit = defineEmits(['signedIn'])

const users = ref([])
const email = ref('')
const error = ref(null)
const busy = ref(false)

onMounted(async () => {
    try {
        const response = await api.get('/dev/idp/users')
        users.value = response.data.data
    } catch {
        error.value = 'Could not load fake IdP users. Is IDP_DRIVER=fake?'
    }
})

async function signIn() {
    if (!email.value || busy.value) {
        return
    }
    busy.value = true
    error.value = null
    try {
        await api.get('/sanctum/csrf-cookie')
        const response = await api.post('/dev/idp/token', { email: email.value }, { skipErrorAlert: true })
        setFakeIdpToken(response.data.token)
        emit('signedIn')
    } catch (e) {
        error.value = e?.response?.data?.errors?.email?.[0] || e?.response?.data?.message || 'Sign-in failed.'
    } finally {
        busy.value = false
    }
}
</script>

<template>
  <div class="border-2 border-dashed border-orange-400 rounded p-4 bg-orange-50">
    <div class="font-bold text-orange-700 mb-2">
      Fake identity provider (development only)
    </div>
    <p class="text-sm mb-3">
      Choose a user to sign in as. This stands in for the external sign-in page.
    </p>
    <form-container @keyup.enter="signIn">
      <label class="block text-sm mb-1" for="fake-idp-user">Known users</label>
      <select id="fake-idp-user" v-model="email" class="w-full mb-2">
        <option value="">
          – choose –
        </option>
        <option v-for="u in users" :key="u.email" :value="u.email">
          {{ u.name || u.email }} &lt;{{ u.email }}&gt; ({{ u.source }})
        </option>
      </select>
      <input-row
        v-model="email"
        label="…or type an email"
        type="text"
        name="fake-idp-email"
        :errors="error ? [error] : []"
      />
      <button-row class="mt-3">
        <button class="btn blue w-auto px-4" name="fake-idp-sign-in" :disabled="busy || !email" @click="signIn">
          Sign in with fake IdP
        </button>
      </button-row>
    </form-container>
  </div>
</template>
