<script setup>
    import {ref} from 'vue'
    import ReviewCommentForm from './ReviewCommentForm.vue'
    import commentFormFactory from '@/forms/comment_form.js'
    import { useStore } from 'vuex'
    import setupReviewData from '../../composables/setup_review_data';
    import commentRepository from '../../repositories/comment_repository';
import DropdownItem from '../DropdownItem.vue';

    const store = useStore();
    const {comments, getComments} = setupReviewData(store);

    const formDef = commentFormFactory();

    const props = defineProps({
        comment: {
            type: Object,
            required: true
        }
    });

    const emits = defineEmits(['updated', 'resolved', 'unresolved']);

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

    const initDelete = () => {
        showConfirmDelete.value = true
    }
    const deleteComment = () => {
        formDef.destroy(props.comment)
            .then(() => {
                const delIdx = comments.value.findIndex(i => i.id == props.comment.id)
                comments.value.splice(delIdx, 1);
                showConfirmDelete.value = false;
            })
    }

    const toggleResolution = async () => {
        if (props.comment.is_resolved) {
            await commentRepository.unresolve(props.comment.id)
            emits('updated');
            emits('resolved');
            getComments();
            return;
        }

        await commentRepository.resolve(props.comment.id)
        emits('updated');
        emits('unresolved');
        getComments();
    }

    const showReplyForm = ref(false);
    const initReply = () => {
        showReplyForm.value = true
    }
</script>
<template>
    <div class="my-2">
        <div v-if="!showEditForm" class="relative">
            <div class="flex justify-between items-start mb-1 rounded">
                <div class="flex space-x-2 items-center">
                    <strong class="block" style="font-size: 1rem">{{comment.creator.name}}</strong>
                    <badge class="block" :color="getVariant(comment)">{{titleCase(comment.type.name)}}</badge>
                    <icon-checkmark class="text-green-500" v-if="comment.is_resolved" />

                </div>
                <div class="flex space-x-2">
                    <dropdown-menu hideCheveron>
                        <dropdown-item @click="showEditForm = true">Edit</dropdown-item>
                        <dropdown-item @click="initReply">Reply</dropdown-item>
                        <dropdown-item @click="toggleResolution">{{comment.is_resolved ? 'Mark unresolved' : 'Resolve'}}</dropdown-item>
                        <dropdown-item @click="initDelete">Delete</dropdown-item>
                    </dropdown-menu>
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
        <ReviewCommentForm v-else @canceled="showEditForm = false" :comment="comment" @saved="showEditForm = false" />
        
        <ul class="ml-4">
            <li v-for="reply in comment.comments" :key="reply.id">
                <ReviewComment :comment="reply"></ReviewComment>
            </li>
        </ul>
        <ReviewCommentForm v-show="showReplyForm" @canceled="showReplyForm = false" @saved="showReplyForm = false" />
    </div>
</template>