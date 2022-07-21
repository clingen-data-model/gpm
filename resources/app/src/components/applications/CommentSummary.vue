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
    <table class="mt-2">
        <tr v-for="(comments, section) in commentsBySection" :key="section">
            <td class=" border-none">
                <h4>{{titleCase(section)}}</h4>
            </td>
            <td class=" border-none">
                <ReviewCommentAnonymous 
                    v-for="comment in comments" 
                    :key="comment.id" 
                    :comment="comment" 
                    class="mb-4"
                />
            </td>
        </tr>
        <tr>
            <td class="border-none"></td>
            <td class="border-none">
                <note>
                    To make changes to comments click 'Cancel' and update alongside the application.
                </note>
            </td>
        </tr>
    </table>        
</template>