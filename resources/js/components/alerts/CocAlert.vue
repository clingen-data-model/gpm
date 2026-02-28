<script setup>
import { computed } from 'vue'

const props = defineProps({
  coc: {
    type: Object,
    default: null
  }
})

const status = computed(() => props.coc?.status ?? 'missing')
const expiresAt = computed(() => props.coc?.expires_at ?? null)
const daysRemaining = computed(() => props.coc?.days_remaining)

const isMissing = computed(() => status.value === 'missing' || status.value === 'version_mismatch')
const isExpiringSoon = computed(() => status.value === 'expiring_soon')
const isExpired = computed(() => status.value === 'expired')

const show = computed(() => isMissing.value || isExpired.value || isExpiringSoon.value)

const variant = computed(() => {
  if (isMissing.value || isExpired.value) return 'danger'
  if (isExpiringSoon.value) return 'warning'
  return 'info'
})

const cocLink = computed(() => ({ name: 'PendingCocAttestation' }))

const message = computed(() => {
  if (isMissing.value) {
    return 'You need to complete your Code of Conduct attestation.'
  }
  if (isExpired.value) {
    return 'Your Code of Conduct attestation is expired.'
  }
  if (isExpiringSoon.value) {
    const dr = typeof daysRemaining.value === 'number' ? daysRemaining.value : null
    return dr !== null
      ? `Your Code of Conduct attestation will expire in ${dr} day${dr === 1 ? '' : 's'}.`
      : 'Your Code of Conduct attestation is about to expire.'
  }
  return ''
})

const formatDate = (iso) => {
  if (!iso) return null
  return new Date(iso).toLocaleDateString()
}
</script>

<template>
  <static-alert
    v-if="show"
    class="pb-3 flex space-x-2 items-center"
    :variant="variant"
  >
    <icon-exclamation height="30" width="30" />
    <div>
      <p>
        <strong>Code of Conduct:</strong>
        {{ message }}
        <span v-if="expiresAt && (isExpired || isExpiringSoon)">
          (Expiration {{ formatDate(expiresAt) }})
        </span>
      </p>

      <router-link :to="cocLink" class="btn font-bold">
        Review &amp; attest Code of Conduct
      </router-link>
    </div>
  </static-alert>
</template>
