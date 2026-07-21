<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import CoreMemberAttestationForm from '@/components/people/CoreMemberAttestationForm.vue'

const store = useStore()
const router = useRouter()

const personUUID = computed(() =>
    store.getters.currentUser?.person?.uuid
)

async function goToDashboard() {
    await router.replace({ name: 'Dashboard' })
}
</script>

<template>
    <card title="Core Approval Member Attestation">
        <p class="mb-4">
            You've been designated as a Core Approval member.
            Please complete this one-time attestation to continue.
        </p>

        <CoreMemberAttestationForm
            v-if="personUUID"
            :person-uuid="personUUID"
            :after-submit="goToDashboard"
        />
    </card>
</template>