<script setup>
    import { ref, defineProps, onMounted, computed } from 'vue'
    import { titleCase } from '@/utils.js'
    import { formatDate } from '@/date_utils.js'
    import ReviewComment from '@/components/expert_panels/ReviewComment.vue'

    const props = defineProps({
        title: {
            type: String || null,
            default: null
        },
        name: {
            type: String || null,
            default: null
        }
    });

    const comments = ref([
        {
            id: 1,
            type: {
                name: 'required revision',
                id: 1
            },
            comment: 'Your short base name is very bad.  It smells funny and we would like you to change it to something that isn\'t so odious',
            section: 'basic-info',
            commenter: {
                name: 'Core Group'
            },
            created_at: new Date(),
            comments: [
                {
                    id: 3,
                    type: {
                        name: 'internal comment',
                        id: 2
                    },
                    comment: 'I don\'t know that it\'s that bad',
                    commenter: {
                        name: 'Rando McCommenter'
                    },
                }
            ]
        },
        {
            id: 2,
            type: {
                name: 'suggestion',
                id: 1
            },
            comment: 'We think you need better people on your expert panel all of these peope are terrible. You don\t have a single Schlorpian on your team.',
            section: 'membership',
            commenter: {
                name: 'Core Group'
            },
            created_at: new Date()
        } 
        
    ]);

    const showComments = ref(true)
    const showCommentForm = ref(false)

    const sectionComments = computed(() => {
        return comments.value.filter(c => c.section == props.name)
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
    <section class="flex space-x-4">
        <div class="overflow-x-scroll flex-grow" :class="{'w-3/5': showComments}">
            <header class="flex justify-between items-start space-x-4">
                <h2 class="flex-grow" :class="{'w-3/5': !showComments}">{{title}}</h2>
                <div class="flex justify-between items-center w-2/5 px-2 py-1 pb-0 bg-gray-100 rounded-lg" v-show="!showComments">
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
        <div class="w-2/5 p-2 bg-gray-100 rounded-lg" v-show="showComments">
            <div class="flex justify-between items-center">
                <h3>
                    <icon-cheveron-down class="inline cursor-pointer" @click="showComments = false" />
                    Comments
                </h3>
            </div>
            <ul>
                <li v-for="comment in sectionComments" :key="comment.id" class="bg-white p-2">
                    <ReviewComment :comment="comment"></ReviewComment>
                </li>
            </ul>
            <div v-if="showCommentForm">
                
            </div>
            <button v-else class="mt-2 btn btn-xs block">Add comment</button>
        </div>
    </section>
</template>