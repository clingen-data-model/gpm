<script setup>
    import {ref, computed, onMounted} from 'vue'
    import {useStore} from 'vuex'
    import ReviewCommentForm from './ReviewCommentForm.vue'
    import commentFormFactory from '@/forms/comment_form.js'
    import commentRepository from '../../repositories/comment_repository';
    import DropdownItem from '../DropdownItem.vue';
    import commentManagerFactory from '@/composables/comment_manager.js'
    import {hasPermission} from '../../auth_utils';

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

    const store = useStore();

    const formDef = commentFormFactory();
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
                break
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
    
    const showReplies = () => {
        replyManager.value.getComments()
            .then(rsp => {
                showReplyList.value = true;
                return rsp;
            });
    }

    const toggleReplies = async () => {
        if (!showReplyList.value) {
            showReplies();
        }
        showReplyList.value = !showReplyList.value;
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

    const canEdit = computed(() => hasPermission('comments-manage') || store.getters.currentUser.person.id === props.comment.creator_id)
</script>
<template>
  <div class="my-2">
    <div class="comment-container">
      <div v-if="!showEditForm" class="relative">
        <div class="flex justify-between items-start mb-1 rounded">
          <div class="flex space-x-2 items-end">
            <strong class="block">{{ comment.creator && comment.creator.name }}</strong>
            <badge class="block" :color="getVariant(comment)" size="xxs">
              {{ comment.type && titleCase(comment.type.name) }}
            </badge>
            <popper v-if="comment.is_resolved" hover arrow content="Resolved">
              <icon-checkmark
                class="text-green-500"
                title="Resolved"
              />
            </popper>
          </div>
          <div class="flex space-x-2">
            <dropdown-menu v-if="canEdit" hide-cheveron>
              <DropdownItem @click="showEditForm = true">
                Edit
              </DropdownItem>
              <DropdownItem @click="toggleResolution">
                {{ comment.is_resolved ? 'Mark unresolved' : 'Resolve' }}
              </DropdownItem>
              <DropdownItem @click="initDelete">
                Delete
              </DropdownItem>
            </dropdown-menu>
          </div>
        </div>

        <markdown-block :markdown="comment.content" class="text-sm" />
        <button class="link" @click="initReply">
          <icon-reply class="inline-block" />Reply
        </button>


        <static-alert v-show="showConfirmDelete" variant="danger" class="">
          Continue with delete?
          <button-row
            size="xs"
            submit-text="Yes, delete"
            submit-variant="red"
            @submitted="deleteComment"
            @canceled="showConfirmDelete = false"
          />
        </static-alert>
      </div>
      <ReviewCommentForm
        v-else
        :comment="comment"
        :comment-manager="commentManager"
        @canceled="showEditForm = false"
        @saved="showEditForm = false"
      />
    </div>


    <div class="replies ml-1">
      <ul v-if="showReplyList">
        <li
          v-for="reply in replyManager.comments" :key="reply.id"
          class="border-l-2 mt-2 px-2 py-1 bg-gray-100/50"
        >
          <ReviewComment :comment="reply" :comment-manager="replyManager" @deleted="handleReplyRemoved" />
        </li>
      </ul>
      <button v-if="comment.comments_count > 0" class="link text-sm" @click="toggleReplies">
        {{ showReplyList ? 'Hide' : 'Show' }}
        {{ comment.comments_count }}
        {{ comment.comments_count > 1 ? 'replies' : 'reply' }}
      </button>
      <div v-show="showReplyForm" class="border-l-2 mt-2 px-2 py-1 bg-gray-100/50">
        <strong>Your Reply</strong>
        <ReviewCommentForm
          subject-type="App\Models\Comment"
          :subject-id="comment.id"
          :only-internal="true"
          :comment-manager="replyManager"
          @saved="handleNewReply"
          @canceled="showReplyForm = false"
        />
      </div>
    </div>
  </div>
</template>
