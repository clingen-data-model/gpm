<script setup>
import { defineAsyncComponent } from 'vue'
import { idpDriver } from '@/idp/config'
import FakeIdpSignIn from './FakeIdpSignIn.vue'

/**
 * Renders the sign-in UI of whichever identity provider is configured.
 * Emits `signedIn` when the fake driver completes; Clerk's own component
 * updates useAuth().isSignedIn instead, which the login page watches.
 */
/**
 * Where Clerk's hosted component should land after sign-in. Defaults to the
 * current page so flows like invite redemption keep their URL.
 */
const props = defineProps({
    redirectUrl: {
        type: String,
        default: () => window.location.pathname + window.location.search,
    },
})
defineEmits(['signedIn'])

const driver = idpDriver()

const ClerkSignIn = defineAsyncComponent(() => import('@clerk/vue').then(m => m.SignIn))
</script>

<template>
  <div class="flex justify-center">
    <ClerkSignIn v-if="driver === 'clerk'" routing="hash" :force-redirect-url="props.redirectUrl" />
    <FakeIdpSignIn v-else-if="driver === 'fake'" class="w-full" @signed-in="$emit('signedIn')" />
  </div>
</template>
