<template>
    <icon-base :icon-name="title || 'checkmark icon'">
        <path d="M0 11l2-2 5 5L18 3l2 2L7 18z"/>
    </icon-base>
</template>
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