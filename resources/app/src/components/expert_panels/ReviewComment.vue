<script setup>
    import {defineProps} from 'vue'

    const props = defineProps({
        comment: {
            type: Object,
            required: true
        }

    })

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
</script>
<template>
    <div class="my-2">
        <div class="flex justify-between items-start mb-1 rounded">
            <div class="flex space-x-2">
                <strong class="block" style="font-size: 1rem">{{comment.creator.name}}</strong>
                <badge class="block" :color="getVariant(comment)">{{titleCase(comment.type.name)}}</badge>
            </div>
            <div class="flex space-x-2">
                <button class="btn btn-xs"><icon-edit width="10" height="10"></icon-edit></button>
                <button class="btn btn-xs"><icon-reply width="10" height="10"></icon-reply></button>
            </div>
        </div>

        <markdown-block :markdown="comment.content" class="text-sm" />
        
        <ul class="ml-4">
            <li v-for="reply in comment.comments" :key="reply.id">
                <ReviewComment :comment="reply"></ReviewComment>
            </li>
        </ul>
    </div>
</template>