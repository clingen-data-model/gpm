<script setup>
import { useStore } from 'vuex'
import CoreMemberAttestationForm from '@/components/people/CoreMemberAttestationForm.vue'

const store = useStore()
const personUUID = store.getters.currentUser?.person?.uuid

async function continueAfterAttestation() {
    const redirectTo = route.query.redirect
    if (typeof redirectTo === 'string' && redirectTo.startsWith('/') && !redirectTo.startsWith('/onboarding/') && redirectTo !== '/core-approval-member-attestation') {
        await router.replace(redirectTo)
        return
    }
    await router.replace({ name: 'Dashboard' })
}
</script>

<template>
    <card title="Core Approval Member Attestation">
        <p class="mb-4">You've been designated as a Core Approval member. Please complete this one-time attestation to continue.</p>

        <CoreMemberAttestationForm v-if="personUUID" :person-uuid="personUUID" :after-submit="continueAfterAttestation" />
    </card>
</template>
