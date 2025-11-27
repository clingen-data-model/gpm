<template>
  <div v-if="show" class="bg-yellow-100 text-yellow-800 p-3 rounded mb-4">
    <strong>Note:</strong>
    This section is locked because the application has been approved.
    Coordinators can still make changes. If you need an update, please
    contact your group coordinator or
    <a :href="`mailto:${supportEmail}`">GPM Support</a>.
  </div>
</template>

<script setup>
const { show, supportEmail } = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  supportEmail: {
    type: String,
    default: 'gpm_support@clinicalgenome.org',
  },
});
</script>
