<script setup>
    import {ref} from 'vue'
    import commentManagerFactory from '@/composables/comment_manager.js'

    const props = defineProps({
        comment: {
            type: Object,
            required: true
        }
    });

    const replyManager = ref(commentManagerFactory('App\\Models\\Comment', props.comment.id))


    const getVariant = comment => {
        switch (comment.type.name) {
            case 'required revision':
                return 'yellow'
            case 'suggestion':
                return 'blue'
            case 'internal comment':
                return 'gray'
            default:
                return null
        }
    }

    const showReplyList = ref(false);
    const toggleReplies = async () => {
        if (!showReplyList.value) {
            await replyManager.value.getComments();
        }
        showReplyList.value = !showReplyList.value;
    }
</script>
<template>
  <div>
    <div class="comment-container">
      <div class="flex items-start space-x-2">
        <popper :content="titleCase(comment.type.name)" hover arrow placement="left">
          <badge class="block" :color="getVariant(comment)" size="xxs">
            {{ titleCase(comment.type.name.substr(0,1)) }}
          </badge>
        </popper>
        <div>
          <markdown-block :markdown="comment.content" class="text-sm" />
          <div class="replies ml-1">
            <ul v-if="showReplyList" class="">
              <li v-for="reply in replyManager.comments" :key="reply.id" class="border-l-2 mt-2 px-2 py-1">
                <ReviewCommentAnonymous :comment="reply" :comment-manager="replyManager" @deleted="handleReplyRemoved" />
              </li>
            </ul>
            <button v-if="comment.comments_count > 0" class="link text-sm" @click="toggleReplies">
              {{ showReplyList ? 'Hide' : 'Show' }}
              {{ comment.comments_count }}
              {{ comment.comments_count > 1 ? 'replies' : 'reply' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
