<script setup>
    import { ref, computed } from 'vue'
    import ReviewComment from '@/components/expert_panels/ReviewComment.vue'
    import ReviewCommentForm from './ReviewCommentForm.vue'
    import {useStore} from 'vuex'
    import setupReviewData from '@/composables/setup_review_data.js'

    const store = useStore();
    const { comments } = setupReviewData(store);

    console.log(comments)

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

    const showComments = ref(true)

    const sectionComments = computed(() => {
        return comments.value.filter(c => {
            if (c.metadata) {
                return c.metadata.section == props.name
            }
            return false
        })
    })

    const countColor = computed(() => {
        if (sectionComments.value.find(c => c.type.name == 'required revision')) {
            return 'yellow'
        }
        if (sectionComments.value.find(c => c.type.name == 'suggestion')) {
            return 'blue'
        }
        return 'gray'
    })
</script>

<template>
    <section class="space-y-4 lg:flex lg:space-x-4">
        <div class="overflow-x-scroll flex-grow" :class="{'lg:w-3/5': showComments}">
            <header class="flex justify-between items-start space-x-4">
                <h2 class="flex-grow" :class="{'lg:w-3/5': !showComments}">{{title}}</h2>
                <div class="flex justify-between items-center lg:w-2/5 px-2 py-1 pb-0 bg-gray-100 rounded-lg" v-show="!showComments">
                    <h3>
                        <icon-cheveron-right class="inline cursor-pointer" @click="showComments = true" />
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
        <div class="lg:w-2/5 p-2 bg-gray-100 rounded-lg" v-show="showComments">
            <div class="flex justify-between items-center">
                <h3>
                    <icon-cheveron-down class="inline cursor-pointer" @click="showComments = false" />
                    Comments
                </h3>
            </div>
            <ul>
                <li v-for="comment in sectionComments" :key="comment.id" class="bg-white p-2 mb-2">
                    <ReviewComment :comment="comment"></ReviewComment>
                </li>
            </ul>
            <ReviewCommentForm :section="name" />
        </div>
    </section>
</template>