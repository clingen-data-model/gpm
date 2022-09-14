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
        <table class="mt-2" v-if="comments.length > 0">
            <tr v-for="(comments, section) in commentsBySection" :key="section">
                <td class="w-40 border-none">
                    <h4>{{titleCase(section)}}</h4>
                </td>
                <td class=" border-none">
                    <ReviewCommentAnonymous
                        v-for="comment in comments"
                        :key="comment.id"
                        :comment="comment"
                        class="mb-1"
                    />
                </td>
            </tr>
        </table>
        <div v-else>
            No comments
        </div>
    </div>
</template>
