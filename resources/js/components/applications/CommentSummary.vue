<script setup>
    import {computed} from 'vue';
    import ReviewCommentAnonymous from '@/components/expert_panels/ReviewCommentAnonymous.vue'

    const props = defineProps({
        comments: {
            type: Array,
            required: true
        }
    });

    const commentsBySection = computed(() => {
        const sections = {}
        props.comments.forEach(c => {
            const section = c.metadata.section || 'general'
            if (!sections[c.metadata.section]) {
                sections[section] = [];
            }
            sections[section].push(c)
        })
        return sections
    });
</script>

<template>
    <div>
        <div v-for="(sectionComments, section) in commentsBySection" :key="section"
            class="md:flex md:space-x-4 mt-3 ml-2"
        >
            <h4 class="md:w-1/5 flex-shrink-0">{{ titleCase(section) }}</h4>
            <ReviewCommentAnonymous
                v-for="comment in sectionComments"
                :key="comment.id"
                :comment="comment"
                class="mb-1 flex-grow-0"
            />
        </div>
        <div v-if="sectionComments.length == 0">
            No comments
        </div>
    </div>
</template>
