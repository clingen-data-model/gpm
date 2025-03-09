<script>
export default {
    name: 'ComponentName',
    props: {
        loading: {
            type: Boolean,
            default: false
        }
    },
    emits: ['click'],
}
</script>
<template>
  <button class="btn btn-icon-sm" @click="$emit('click')">
    <icon-refresh :class="{rotate: loading}" width="16" height="16" />
  </button>
</template>
<style>
    .rotate {
        animation: rotation 2s linear infinite;
    }
    @keyframes rotation {
        100% {
            transform: rotate(360deg)
        }
    }
</style>