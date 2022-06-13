<script setup>
    import {defineProps} from 'vue'
    defineProps({
        url: {
            type: String,
            required: true
        },
        title: {
            type: String,
            default: 'download'
        },
    })
</script>

<template>
    <a 
        :href="url"
        :title="title"
    >
        <icon-download class="inline" />&nbsp;
        <slot></slot>
    </a>
</template>