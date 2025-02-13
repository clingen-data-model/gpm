<script setup>
    import {ref, computed, onMounted} from 'vue'
    import {useStore} from 'vuex'
    import ReviewCommentForm from './ReviewCommentForm.vue'
    import commentFormFactory from '@/forms/comment_form.js'
    import commentRepository from '../../repositories/comment_repository';
    import DropdownItem from '../DropdownItem.vue';
    import commentManagerFactory from '@/composables/comment_manager.js'
    import {hasPermission} from '../../auth_utils';

    const store = useStore();

    const formDef = commentFormFactory();
    const props = defineProps({
        comment: {
            type: Object,
            required: true
        },
        commentManager: {
            type: Object,
            required: true
        }
    });
    const emits = defineEmits(['created', 'updated', 'resolved', 'unresolved', 'deleted']);

    const replyManager = ref(commentManagerFactory('App\\Models\\Comment', props.comment.id))

    const showEditForm = ref(false);
    const showConfirmDelete = ref(false);

    const getVariant = comment => {
        if (!comment.type) {
            return 'gray'
        }
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
                showConfirmDelete.value = false;
                props.commentManager.removeComment(props.comment);
                emits('deleted', props.comment);
            })
    }

    const toggleResolution = async () => {
        if (props.comment.is_resolved) {
            const updatedCmt = await commentRepository.unresolve(props.comment.id)
            props.commentManager.updateComment(updatedCmt)
            emits('updated');
            emits('resolved');
            return;
        }

        const updatedCmt = await commentRepository.resolve(props.comment.id)
        props.commentManager.updateComment(updatedCmt)
        emits('updated');
        emits('unresolved');
    }

    const showReplyList = ref(false);
    const toggleReplies = async () => {
        if (!showReplyList.value) {
            showReplies();
        }
        showReplyList.value = !showReplyList.value;
    }

    const showReplies = () => {
        replyManager.value.getComments()
            .then(rsp => {
                showReplyList.value = true;
                return rsp;
            });
    }

    const showReplyForm = ref(false);
    const initReply = () => showReplyForm.value = true;
    const handleNewReply = () => {
        const commentClone = {...props.comment};
        commentClone.comments_count += 1;
        props.commentManager.updateComment(commentClone);
        showReplyList.value = true;
        showReplyForm.value = false;
    }
    const handleReplyRemoved = () => {
        const commentClone = {...props.comment};
        commentClone.comments_count -= 1;
        props.commentManager.updateComment(commentClone);
    }

    onMounted(() => {
        replyManager.value.getComments();
    })

    const canEdit = computed(() => hasPermission('comments-manage') || store.getters.currentUser.person.id == props.comment.creator_id)
</script>
<template>
    <div class="my-2">
        <div class="comment-container">
            <div v-if="!showEditForm" class="relative">
                <div class="flex justify-between items-start mb-1 rounded">
                    <div class="flex space-x-2 items-end">
                        <strong class="block">{{comment.creator && comment.creator.name}}</strong>
                        <badge class="block" :color="getVariant(comment)" size="xxs">
                            {{comment.type && titleCase(comment.type.name)}}
                        </badge>
                        <popper hover arrow content="Resolved" v-if="comment.is_resolved">
                            <icon-checkmark
                                class="text-green-500"
                                title="Resolved"
                            />
                        </popper>
                    </div>
                    <div class="flex space-x-2">
                        <dropdown-menu hideCheveron v-if="canEdit">
                            <dropdown-item @click="showEditForm = true">Edit</dropdown-item>
                            <dropdown-item @click="toggleResolution">{{comment.is_resolved ? 'Mark unresolved' : 'Resolve'}}</dropdown-item>
                            <dropdown-item @click="initDelete">Delete</dropdown-item>
                        </dropdown-menu>
                    </div>
                </div>

                <markdown-block :markdown="comment.content" class="text-sm" />
                <button class="link" @click="initReply"><icon-reply class="inline-block" />Reply</button>


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
            <ReviewCommentForm v-else
                @canceled="showEditForm = false"
                @saved="showEditForm = false"
                :comment="comment"
                :commentManager="commentManager"
            />
        </div>


        <div class="replies ml-1">
            <ul v-if="showReplyList">
                <li v-for="reply in replyManager.comments" :key="reply.id"
                    class="border-l-2 mt-2 px-2 py-1 bg-gray-100 bg-opacity-50"
                >
                    <ReviewComment :comment="reply" :commentManager="replyManager" @deleted="handleReplyRemoved" />
                </li>
            </ul>
            <button class="link text-sm" v-if="comment.comments_count > 0" @click="toggleReplies">
                {{showReplyList ? 'Hide' : 'Show'}}
                {{comment.comments_count}}
                {{comment.comments_count > 1 ? 'replies' : 'reply'}}
            </button>
            <div v-show="showReplyForm" class="border-l-2 mt-2 px-2 py-1 bg-gray-100 bg-opacity-50">
                <strong>Your Reply</strong>
                <ReviewCommentForm
                    subjectType="App\Models\Comment"
                    :subjectId="comment.id"
                    :onlyInternal="true"
                    :commentManager="replyManager"
                    @saved="handleNewReply"
                    @canceled="showReplyForm = false"
                />
            </div>
        </div>
    </div>
</template>
