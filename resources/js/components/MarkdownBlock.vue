<script setup>
import { htmlFromMarkdown } from '@/markdown-utils';
import DOMPurify from 'dompurify';
import { computed } from 'vue';

const props = defineProps({
    markdown: {
        type: String,
        required: false,
    }
});

const rendered = computed(() => {
    if (!props.markdown) return null;

    return DOMPurify.sanitize(htmlFromMarkdown(props.markdown));
});
</script>
<template>
  <div class="markdown" v-html="rendered" />
</template>