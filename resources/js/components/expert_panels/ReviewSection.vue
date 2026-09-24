<script setup>
    import { ref, computed, inject } from 'vue'
    import ReviewComment from '@/components/expert_panels/ReviewComment.vue'
    import ReviewCommentForm from './ReviewCommentForm.vue'

    const props = defineProps({
        title: {
            type: String || null,
            default: null
        },
        name: {
            type: String || null,
            default: null
        },
    });

    const commentManager = inject('commentManager')

    const showCommentForm = ref(false);

    const showComments = ref(true)
    const sectionComments = computed(() => {
        if (!commentManager) {
            return [];
        }
        return commentManager.value.comments.filter(c => {
            if (c.metadata) {
                return c.metadata.section === props.name
            }
            return false
        })
    })

    const categories = computed(() => ['required revision', 'internal comment', 'suggestion'].map(name => {
        const comments = sectionComments.value.filter(comment => comment.type?.name === name);
        return { key: name.replaceAll(' ', '_'), label: name === 'required revision' ? 'Required Revisions' : name === 'internal comment' ? 'Internal Comments' : 'Suggestions',
            outstanding: comments.filter(comment => !comment.is_resolved),
            resolved: comments.filter(comment => comment.is_resolved) };
    }).filter(category => category.outstanding.length || category.resolved.length));

    const countColor = computed(() => {
        if (!commentManager) {
            return null;
        }
        if (sectionComments.value.find(c => c.type.name === 'required revision')) {
            return 'yellow'
        }
        if (sectionComments.value.find(c => c.type.name === 'suggestion')) {
            return 'blue'
        }
        return 'gray'
    })
</script>

<template>
  <section class="lg:flex lg:space-x-4 screen-block">
    <div class="overflow-x-auto flex-grow" :class="{'lg:w-3/5': showComments}">
      <header class="flex justify-between items-start space-x-4">
        <h2 class="flex-grow" :class="{'lg:w-3/5': !showComments}">
          {{ title }}
        </h2>
        <div v-show="!showComments" v-if="commentManager" class="flex justify-between items-center lg:w-2/5 px-2 py-1 pb-0 bg-gray-100 rounded-lg">
          <h3>
            <icon-cheveron-right class="inline cursor-pointer" @click="showComments = true" />
            Comments
            <badge :color="countColor">
              {{ sectionComments.length }}
            </badge>
          </h3>
        </div>
      </header>
      <div>
        <div>
          <slot />
        </div>
      </div>
    </div>

    <div v-show="showComments && commentManager" class="lg:w-2/5 p-2 bg-gray-100 rounded-lg mb-2">
      <div class="flex justify-between items-center">
        <h3>
          <icon-cheveron-down class="inline cursor-pointer" @click="showComments = false" />
          Comments
        </h3>
      </div>
      <section v-for="category in categories" :key="category.key" :data-comment-category="category.key" class="mt-3">
        <h4>{{ category.label }}</h4>
        <ul>
          <li v-for="comment in category.outstanding" :key="comment.id" class="bg-white p-2">
            <ReviewComment :comment="comment" :comment-manager="commentManager" />
          </li>
        </ul>
        <details v-if="category.resolved.length" class="mt-2">
          <summary class="cursor-pointer">Resolved comments ({{ category.resolved.length }})</summary>
          <ul>
            <li v-for="comment in category.resolved" :key="comment.id" class="bg-white p-2">
              <ReviewComment :comment="comment" :comment-manager="commentManager" />
            </li>
          </ul>
        </details>
      </section>
      <div class="bg-white mt-2 p-2">
        <ReviewCommentForm
          v-if="showCommentForm"
          :section="name"
          :comment-manager="commentManager"
          @saved="showCommentForm = false"
          @canceled="showCommentForm = false"
        />
        <button v-else class="btn btn-xs block" @click="showCommentForm = true">
          Add comment
        </button>
      </div>
    </div>
  </section>
</template>
