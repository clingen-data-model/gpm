<script setup>
import { useStore } from 'vuex'
import CoreMemberAttestationForm from '@/components/people/CoreMemberAttestationForm.vue'

const store = useStore()
const personUUID = store.getters.currentUser?.person?.uuid

function backOrHome() {
    history.length > 1 ? window.history.back() : location.assign('/')
}
</script>

<template>
    <card title="Core Approval Member Attestation">
        <p class="mb-4">You've been designated as a Core Approval member for a VCEP. Please complete this one-time attestation to continue.</p>

        <CoreMemberAttestationForm v-if="personUUID" :person-uuid="personUUID" :after-submit="backOrHome" />
    </card>
</template>
