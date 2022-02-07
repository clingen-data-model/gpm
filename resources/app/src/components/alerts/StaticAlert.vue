<style lang="postcss" scoped>
    .danger {
        @apply bg-red-200 text-red-800 border-red-500;
    }
    .warning {
        @apply bg-yellow-200 text-yellow-700 border-yellow-500;
    }
    .success {
        @apply bg-green-200 text-green-800 border-green-500;
    }
    .info {
        @apply bg-blue-200 text-blue-800 border-blue-500;
    }
</style>
<template>
    <div class="border p-2 rounded text-md-lg" :class="variant">
        <slot></slot>
    </div>
</template>
<script>
export default {
    name: 'ComponentName',
    props: {
        variant: {
            type: String,
            required: false,
            default: 'info'
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
    },
    methods: {

    }
}
</script>