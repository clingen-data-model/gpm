<script>
import clerk, { clerkReady } from '@/clerk'

/**
 * Mounts Clerk's prebuilt Sign Up widget for invited users. The email is
 * prefilled from the invite. When sign-up completes (a Clerk session appears)
 * the parent links the new Clerk identity to the local invite/person.
 */
export default {
    name: 'ClerkSignUp',
    props: {
        email: {
            type: String,
            default: '',
        },
    },
    emits: ['signed-up'],
    async mounted() {
        await clerkReady

        // If a session already exists (e.g. returning to this step), proceed.
        if (clerk.user) {
            this.$emit('signed-up')
            return
        }

        this.unsubscribe = clerk.addListener(({ user }) => {
            if (user) {
                this.$emit('signed-up')
            }
        })

        clerk.mountSignUp(this.$refs.signUp, {
            routing: 'hash',
            signInUrl: '/login',
            initialValues: { emailAddress: this.email },
        })
    },
    beforeUnmount() {
        if (this.unsubscribe) {
            this.unsubscribe()
        }
        if (this.$refs.signUp) {
            clerk.unmountSignUp(this.$refs.signUp)
        }
    },
}
</script>
<template>
  <div ref="signUp" />
</template>
