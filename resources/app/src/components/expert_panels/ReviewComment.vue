<script setup>
    import {ref} from 'vue'
    import ReviewCommentForm from './ReviewCommentForm.vue'
    import commentFormFactory from '@/forms/comment_form.js'
    import { useStore } from 'vuex'
    import setupReviewData from '../../composables/setup_review_data';

    const store = useStore();
    const {comments} = setupReviewData(store);

    const formDef = commentFormFactory();

    const props = defineProps({
        comment: {
            type: Object,
            required: true
        }

    })

    const showEditForm = ref(false);
    const showConfirmDelete = ref(false);

    const getVariant = comment => {
        switch (comment.type.name) {
            case 'required revision':
                return 'yellow'
            case 'suggestion':
                return 'blue'
            case 'internal comment':
                return 'gray' 
            default:
                null
                break;
        }
    }

    const deleteComment = () => {
        formDef.destroy(props.comment)
            .then(() => {
                const delIdx = comments.value.findIndex(i => i.id == props.comment.id)
                comments.value.splice(delIdx, 1);
                showConfirmDelete.value = false;
            })
    }
</script>
<template>
    <div class="my-2">
        <div v-if="!showEditForm" class="relative">
            <div class="flex justify-between items-start mb-1 rounded">
                <div class="flex space-x-2">
                    <strong class="block" style="font-size: 1rem">{{comment.creator.name}}</strong>
                    <badge class="block" :color="getVariant(comment)">{{titleCase(comment.type.name)}}</badge>
                </div>
                <div class="flex space-x-2">
                    <icon-edit class="cursor-pointer" width="12" height="12" @click="showEditForm = true" />
                    <icon-trash class="cursor-pointer" width="12" height="12" @click="showConfirmDelete = true" />
                    <icon-reply class="cursor-pointer" width="12" height="12" />
                </div>
            </div>

            <markdown-block :markdown="comment.content" class="text-sm" />

            <static-alert variant="danger" v-show="showConfirmDelete" class="">
                Continue with delete?
                <button-row 
                    size="xs"
                    submit-text="Yes, delete" 
                    @submitted="deleteComment"
                    @canceled="showConfirmDelete=false"
                    submitVariant="red"
                ></button-row>
            </static-alert>
        </div>
        <ReviewCommentForm v-else @canceled="showEditForm = false" :comment="comment" @saved="showEditForm = false"></ReviewCommentForm>
        
        <ul class="ml-4">
            <li v-for="reply in comment.comments" :key="reply.id">
                <ReviewComment :comment="reply"></ReviewComment>
            </li>
        </ul>

    </div>
</template>