<style lang="postcss" scoped>
    .green {
        @apply bg-green-600
    }
    .blue {
        @apply bg-blue-500
    }
    .yellow {
        @apply bg-yellow-500
    }
    .red {
        @apply bg-red-400
    }

</style>
<template>
    <span class="rounded-xl px-2 py-1 text-white text-xs font-bold" :class="color">
        <slot></slot>
    </span>
</template>
<script>
export default {
    name: 'ComponentName',
    props: {
        color: {
            type: String,
            required: false,
            default: 'gray',
        }
    },
}
</script>