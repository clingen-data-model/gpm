<template>
    <div class="markdown" v-html="rendered"></div>
</template>
<script>
import marked from 'marked';
import purify from 'dompurify';
export default {
    name: 'MarkdownBlock',
    props: {
        markdown: {
            required: true,
        }
    },
    computed: {
        rendered () {
            if (!this.markdown) return null;

            return purify.sanitize(marked(this.markdown));
        }
    },
    methods: {

    }
}
</script>