<style lang="postcss" scoped>
    .danger {
        @apply bg-red-200 text-red-800;
    }
    .warning {
        @apply bg-yellow-200 text-yellow-800;
    }
    .success {
        @apply bg-green-200 text-green-800;
    }
    .info {
        @apply bg-blue-200 text-blue-800;
    }
</style>
<template>
    <div class="border p-2 rounded" :class="variant">
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