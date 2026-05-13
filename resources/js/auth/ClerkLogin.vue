<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStore } from 'vuex'
import { SignIn, useAuth } from '@clerk/vue'

const router = useRouter()
const route = useRoute()
const store = useStore()

const { isSignedIn, getToken } = useAuth()

const loading = ref(false)
const errorMessage = ref('')

async function startGpmSession() {
  const token = await getToken.value()
  await store.dispatch('clerkSessionLogin', token)
}

async function redirectAfterLogin() {
  let target = { name: 'Dashboard' }

  if (route.query.redirect) {
    target = route.query.redirect
  }

  router.replace(target)
}

async function finishLogin() {
  if (loading.value) {
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    await startGpmSession()
    await store.dispatch('forceGetCurrentUser')
    await redirectAfterLogin()
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Your Clerk account is authenticated, but it is not linked to a GPM user yet.'
  } finally {
    loading.value = false
  }
}

watch(
  () => isSignedIn.value,
  async (signedIn) => {
    if (signedIn) {
      await finishLogin()
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="container py-4">
    <card title="Continue with Clerk" class="md:w-1/2 mx-auto">
      <div v-if="errorMessage" class="alert alert-danger mb-4">
        {{ errorMessage }}
      </div>

      <div v-if="loading" class="alert alert-info mb-4">
        Signing you in to GPM...
      </div>

      <div class="flex justify-center">
        <SignIn
          v-if="!isSignedIn"
          path="/login/clerk"
          routing="path"
        />
      </div>
    </card>
  </div>
</template>