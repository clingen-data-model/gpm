<script setup>
import { watch } from 'vue'
import { SignUp, useAuth } from '@clerk/vue'

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['signed-up'])

const { isSignedIn } = useAuth()

// If a session already exists (e.g. returning to this step), proceed
// immediately; otherwise fire once sign-up completes.
watch(
    () => isSignedIn.value,
    (signedIn) => {
        if (signedIn) {
            emit('signed-up')
        }
    },
    { immediate: true }
)
</script>

<template>
  <SignUp
    v-if="!isSignedIn"
    routing="hash"
    sign-in-url="/login"
    :initial-values="{ emailAddress: props.email }"
  />
</template>
