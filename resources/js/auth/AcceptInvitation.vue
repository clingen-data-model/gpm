<script setup>
import { useStore } from 'vuex'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth, useSignUp } from '@clerk/vue'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const { getToken, isSignedIn } = useAuth()
const { isLoaded, signUp, setActive } = useSignUp()

const password = ref('')
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const inviteCode = computed(() => route.query.code || '')
const ticket = computed(() => route.query.__clerk_ticket || '')
const store = useStore()

async function finishGpmRedeem() {
  const token = await getToken.value()

  await axios.post('/api/auth/clerk/redeem-invitation', { code: inviteCode.value }, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    }
  )
}

async function startGpmSession() {
  const token = await getToken.value()
  await store.dispatch('clerkSessionLogin', token)
}

async function goToDashboardAfterRedeem() {
  await finishGpmRedeem()
  await startGpmSession()
  router.replace({ name: 'Dashboard' })
  // successMessage.value = 'Invitation accepted successfully. Your GPM account is now linked.'
}

async function acceptInvitation() {
  if (!isLoaded.value || !ticket.value || !inviteCode.value || submitting.value) {
    return
  }

  submitting.value = true
  errorMessage.value = ''

  try {
    const signUpAttempt = await signUp.value.create({
      strategy: 'ticket',
      ticket: ticket.value,
      password: password.value,
    })

    if (signUpAttempt.status !== 'complete') {
      errorMessage.value = `Clerk sign-up is not complete yet. Current status: ${signUpAttempt.status}`
      return
    }

    await setActive.value({
      session: signUpAttempt.createdSessionId,
    })
    await debugClerkAuth()
    await goToDashboardAfterRedeem()
  } catch (error) {
    const clerkCode = error?.errors?.[0]?.code

    if (clerkCode === 'session_exists') {
      try {
        await debugClerkAuth()
        await goToDashboardAfterRedeem()
        return
      } catch (redeemError) {
        errorMessage.value = redeemError?.response?.data?.message || 'Your Clerk session exists, but GPM could not finish redeeming the invitation.'
        return
      }
    }

    errorMessage.value = error?.errors?.[0]?.message || error?.response?.data?.message || error?.message || 'We could not finish linking your invitation in GPM.'
  } finally {
    submitting.value = false
  }
}

async function debugClerkAuth() {
  const token = await getToken.value()

  const response = await axios.get('/api/auth/clerk/me', {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  })

  console.log('Clerk /me response', response.data)
  return response.data
}

watch(
  () => isSignedIn.value,
  async (signedIn) => {
    if (!signedIn || !inviteCode.value || !ticket.value || submitting.value || successMessage.value) {
      return
    }

    try {
      submitting.value = true
      errorMessage.value = ''      
      await goToDashboardAfterRedeem()
    } catch (error) {
      errorMessage.value = error?.response?.data?.message || 'You are already signed in, but GPM could not finish redeeming the invitation.'
    } finally {
      submitting.value = false
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="container py-4">
    <h1 class="mb-3">Accept invitation</h1>

    <div v-if="errorMessage" class="alert alert-danger">
      {{ errorMessage }}
      <div class="mt-2">
        <router-link to="/" class="btn btn-outline-primary btn-sm">
          Go to GPM dashboard
        </router-link>
      </div>
    </div>

    <div v-else-if="successMessage" class="alert alert-success">
      {{ successMessage }}
    </div>

    <form v-else @submit.prevent="acceptInvitation">
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input v-model="password" type="password" class="form-control">
      </div>

      <button class="btn btn-primary" type="submit" :disabled="submitting || !isLoaded">
        {{ submitting ? 'Accepting...' : 'Accept invitation' }}
      </button>

      <div id="clerk-captcha" class="mt-3"></div>
    </form>
  </div>
</template>