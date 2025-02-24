<script setup>
import {computed} from 'vue';

    const props = defineProps({
        summary: {
            type: Object,
            required: true
        }
    })

    const authorString = computed(() => {
        if (!props.summary.authors) {
            return '';
        }
        if (props.summary.authors.length === 0) {
            return '';
        }
        if (props.summary.authors.length === 1) {
            return `${props.summary.authors[0].name}. `;
        }

        return `${props.summary.authors.map(a => a.name)[0]} et al. `;
    })

    const titleString = computed(() => {
        if (!props.summary.title) {
            return '';
        }
        if (props.summary.title.length > 25) {
            return `${props.summary.title.substring(0, 50)}... `;
        }

        return `${props.summary.title}. `;
    })

    const sourceString = computed(() => {
        return `${props.summary.source}. `;
    })
</script>

<template>
    <span v-if="summary">
        {{ authorString }}{{ titleString }}<em>{{ sourceString }}</em>{{ summary.pubdate }}
    </span>
</template>
