<script>
export default {
    name: 'NotificationIcon',
    props: {
        iconName: {
            type: String,
            required: false,
            default: 'Notification'
        }
    }
}
</script>
<template>
  <icon-base :icon-name="iconName">
    <g>
      <path d="M4 8a6 6 0 0 1 4.03-5.67 2 2 0 1 1 3.95 0A6 6 0 0 1 16 8v6l3 2v1H1v-1l3-2V8zm8 10a2 2 0 1 1-4 0h4z" />
    </g>
  </icon-base>
</template>