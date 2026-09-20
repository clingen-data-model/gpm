<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import LoginForm from '@/components/LoginForm.vue'
import IdpSignIn from '@/components/auth/IdpSignIn.vue'
import { useIdp } from '@/idp'

/**
 * Sign-in page. The external identity provider comes first; the local
 * password form stays available underneath. Once the IdP reports a signed-in
 * user, the token is exchanged for a GPM session (store.idpSessionLogin).
 */
const store = useStore()
const route = useRoute()
const router = useRouter()
const idp = useIdp()

const idpEnabled = idp.driver !== 'null'
const showLocalForm = ref(!idpEnabled)
const exchanging = ref(false)
const idpError = ref(null)

const isAuthed = computed(() => store.getters.isAuthed)

function redirect() {
    let target = { name: 'Dashboard' }
    if (route.query.redirect) {
        target = route.query.redirect
    }
    if (route.redirectedFrom && route.redirectedFrom.name !== 'login') {
        target = route.redirectedFrom
    }
    router.push(target)
}

async function finishIdpLogin() {
    if (exchanging.value || isAuthed.value) {
        return
    }
    exchanging.value = true
    idpError.value = null
    try {
        const token = await idp.getToken()
        if (!token) {
            throw new Error('The identity provider did not return a session token.')
        }
        await store.dispatch('idpSessionLogin', token)
        redirect()
    } catch (e) {
        idpError.value = e?.response?.data?.message
            || e?.message
            || 'You are signed in with the identity provider, but GPM could not start a session.'
    } finally {
        exchanging.value = false
    }
}

async function signOutOfIdp() {
    idpError.value = null
    await idp.signOut()
}

watch(isAuthed, (authed) => {
    if (authed) {
        redirect()
    }
}, { immediate: true })

watch(idp.isSignedIn, (signedIn) => {
    if (signedIn) {
        finishIdpLogin()
    }
}, { immediate: true })
</script>

<template>
  <div>
    <card title="Sign in" class="md:w-1/2 mx-auto">
      <template v-if="idpEnabled">
        <div v-if="exchanging" class="alert alert-info mb-4">
          Signing you in to GPM…
        </div>
        <div v-else-if="idpError" class="alert alert-danger mb-4">
          <p>{{ idpError }}</p>
          <button class="btn btn-xs mt-2" name="idp-sign-out" @click="signOutOfIdp">
            Sign out of the identity provider
          </button>
        </div>

        <IdpSignIn v-if="!idp.isSignedIn.value || idpError" @signed-in="finishIdpLogin" />

        <div class="mt-6 border-t pt-4">
          <button class="text-blue-500 underline" name="toggle-local-login" @click="showLocalForm = !showLocalForm">
            {{ showLocalForm ? 'Hide password sign-in' : 'Sign in with your GPM password instead' }}
          </button>
        </div>
      </template>

      <div v-show="showLocalForm" class="mt-4">
        <LoginForm @authenticated="redirect" />
      </div>

      <div class="mt-4 text-sm">
        <router-link class="text-blue-500 underline" :to="{ name: 'RedeemInvite' }">
          Redeem your invite
        </router-link>
      </div>
    </card>
  </div>
</template>
