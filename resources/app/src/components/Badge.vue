<template>
    <span class="rounded-xl px-2 py-1 text-white text-xs font-bold" :class="`bg-${color}-600`">
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

    },
    methods: {

    }
}
</script>