<script setup>
    defineProps({
        url: {
            type: String,
            required: true
        },
        title: {
            type: String,
            default: 'download'
        },
    })
</script>

<template>
  <a 
    :href="url"
    :title="title"
  >
    <icon-download class="inline" />&nbsp;
    <slot />
  </a>
</template>