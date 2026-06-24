<script>
import clerk, { clerkReady } from '@/clerk'

/**
 * Mounts Clerk's prebuilt Sign In widget. Clerk owns the credentials, password
 * reset, email verification and MFA flows. Hash routing keeps Clerk's internal
 * steps self-contained so they don't require dedicated vue-router routes.
 */
export default {
    name: 'ClerkSignIn',
    props: {
        redirectUrl: {
            type: String,
            default: '/',
        },
    },
    async mounted() {
        await clerkReady
        clerk.mountSignIn(this.$refs.signIn, {
            routing: 'hash',
            signUpUrl: '/invites',
            fallbackRedirectUrl: this.redirectUrl,
        })
    },
    beforeUnmount() {
        if (this.$refs.signIn) {
            clerk.unmountSignIn(this.$refs.signIn)
        }
    },
}
</script>
<template>
  <div ref="signIn" />
</template>
