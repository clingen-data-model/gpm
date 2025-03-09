<script>
export default {
    name: 'ComponentName',
    props: {
        color: {
            type: String,
            required: false,
            default: 'gray',
        },
        size: {
            type: String,
            default: 'xs'
        }
    },
    computed: {
        textSize () {
            return `text-${this.size}`
        },
        padding () {
            const x = 'px-2';
            const y = 'py-1';

            if (this.size === 'xxs') {
                return `${x} py-0.5`
            }

            return `${x} ${y}`
        }
    }
}
</script>
<template>
    <span class="rounded-xl text-white font-bold" :class="`${color} ${textSize} ${padding}`">
        <slot></slot>
    </span>
</template>
<style scoped>
    .green {
        @apply bg-green-600;
    }
    .blue {
        @apply bg-blue-500;
    }
    .yellow {
        @apply bg-yellow-500;
    }
    .red {
        @apply bg-red-400;
    }
    .gray {
        @apply bg-gray-400;
    }

</style>
