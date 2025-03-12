<script>
export default {
    props: {
        title: {
            required: false,
            type: String
        }
    }
}
</script>
<template>
  <icon-base :icon-name="title || 'filter'">
    <path d="M12 12l8-8V0H0v4l8 8v8l4-4v-4z" />
  </icon-base>
</template>       