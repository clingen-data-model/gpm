<script setup>
import { defineAsyncComponent } from 'vue'
import { idpDriver } from '@/idp/config'
import FakeIdpSignIn from './FakeIdpSignIn.vue'

/**
 * Renders the sign-in UI of whichever identity provider is configured.
 * Emits `signedIn` when the fake driver completes; Clerk's own component
 * updates useAuth().isSignedIn instead, which the login page watches.
 */
defineEmits(['signedIn'])

const driver = idpDriver()

const ClerkSignIn = defineAsyncComponent(() => import('@clerk/vue').then(m => m.SignIn))
</script>

<template>
  <div class="flex justify-center">
    <ClerkSignIn v-if="driver === 'clerk'" routing="hash" />
    <FakeIdpSignIn v-else-if="driver === 'fake'" class="w-full" @signed-in="$emit('signedIn')" />
  </div>
</template>
