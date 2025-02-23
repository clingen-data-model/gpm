<script setup>
    import { ref, computed, inject } from 'vue'
    import ReviewComment from '@/components/expert_panels/ReviewComment.vue'
    import ReviewCommentForm from './ReviewCommentForm.vue'

    const commentManager = inject('commentManager')

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
                <h2 class="flex-grow" :class="{'lg:w-3/5': !showComments}">{{title}}</h2>
                <div class="flex justify-between items-center lg:w-2/5 px-2 py-1 pb-0 bg-gray-100 rounded-lg" v-show="!showComments"  v-if="commentManager">
                    <h3>
                        <icon-cheveron-right class="inline cursor-pointer" @click="showComments = true"/>
                        Comments
                        <badge :color="countColor">{{sectionComments.length}}</badge>
                    </h3>
                </div>

            </header>
            <div>
                <div>
                    <slot></slot>
                </div>
            </div>
        </div>

        <div class="lg:w-2/5 p-2 bg-gray-100 rounded-lg mb-2" v-show="showComments && commentManager">
            <div class="flex justify-between items-center">
                <h3>
                    <icon-cheveron-down class="inline cursor-pointer" @click="showComments = false" />
                    Comments
                </h3>
            </div>
            <ul>
                <li v-for="comment in sectionComments" :key="comment.id" class="bg-white p-2">
                    <ReviewComment :comment="comment" :commentManager="commentManager"></ReviewComment>
                </li>
            </ul>
            <div class="bg-white mt-2 p-2">
                <ReviewCommentForm v-if="showCommentForm"
                    :section="name"
                    :commentManager="commentManager"
                    @saved="showCommentForm = false"
                    @canceled="showCommentForm = false"
                />
                <button v-else class="btn btn-xs block" @click="showCommentForm = true">Add comment</button>
            </div>
        </div>
    </section>
</template>
