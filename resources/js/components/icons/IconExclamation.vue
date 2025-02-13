<template>
    <icon-base :icon-name="title || 'checkmark icon'">
        <!-- Solid path -->
        <path v-if="!outline" d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zM9 5v6h2V5H9zm0 8v2h2v-2H9z"/>

        <!-- Outline path -->
        <path v-else d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 5h2v6H9V5zm0 8h2v2H9v-2z"/>
    </icon-base>
</template>
<script>
export default {
    props: {
        title: {
            required: false,
            type: String
        },
        outline: {
            required: false,
            type: Boolean,
            default: false
        }
    }
}
</script>       