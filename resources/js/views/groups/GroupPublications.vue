<script setup>
import { ref } from 'vue'
import PublicationManager from '@/components/groups/PublicationManager.vue'
defineProps({ group: { type: Object, required: true } })
const manager = ref(null)
defineExpose({ refresh: () => manager.value?.refresh() })
</script>

<template>
  <PublicationManager ref="manager" :group="group" />
</template>
