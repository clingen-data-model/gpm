<script setup>
import { defineAsyncComponent } from 'vue'
import { idpDriver } from '@/idp/config'

/**
 * Clerk's account UI, where a user manages the sign-in methods on their
 * identity: connected Google/GitHub accounts, additional email addresses,
 * password. GPM stores none of this; it only holds users.idp_id, which does
 * not change when a social account is linked.
 *
 * Signing in with a social account whose verified email matches links it
 * automatically, so this page is for the cases that cannot: a different
 * address, or removing a connection.
 */
const driver = idpDriver()

const ClerkUserProfile = defineAsyncComponent(() => import('@clerk/vue').then(m => m.UserProfile))
</script>

<template>
  <div class="container mx-auto py-6">
    <h1 class="mb-4">
      Sign-in methods
    </h1>

    <div v-if="driver === 'clerk'" class="flex justify-center">
      <ClerkUserProfile routing="hash" />
    </div>
    <p v-else class="text-gray-600">
      Sign-in methods are managed by the identity provider, which is not
      enabled in this environment.
    </p>
  </div>
</template>
