<script setup>
defineProps({ operation: { type: String, default: null } })
</script>
<template>
  <span v-if="operation === 'added' || operation === 'removed'" aria-hidden="true"
    class="pointer-events-none absolute inset-y-0 left-0 w-1"
    :class="operation === 'added' ? 'bg-blue-600' : 'bg-orange-600'" />
</template>
