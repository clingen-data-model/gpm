<template>
    <span class="rounded-xl px-2 py-1 text-white text-xs font-bold" :class="colorClass">
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
    data() {
        return {
        }
    },
    computed: {
        colorClass () {
            let darkness = 500;
            if (this.color == 'green') {
                darkness = 600;
            }

            return `bg-${this.color}-${darkness}`;
        }
    },
    methods: {

    }
}
</script>