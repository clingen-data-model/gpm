<script setup>
    import { ref, computed } from 'vue';
    import { useStore } from 'vuex'
    import setupReviewData from '../../composables/setup_review_data';
    import formDefFactory from '../../forms/comment_form.js';

    const props = defineProps({ 
        section: {
            type: [String, null], 
            default: null
        },
        subjectType: { 
            type: String, 
            default: '\\App\\Modules\\Group\\Models\\Group'
        }
    });
    const emits = defineEmits(['saved', 'canceled']);

    const store = useStore();
    const {group, comments} = setupReviewData(store);

    const formDef = formDefFactory();

    const fields = computed(() => {
        return formDef.fields.value
    })
    const errors = computed(() => {
        return formDef.errors.value
    })

    const newComment = computed({
        get () {
            return formDef.currentItem.value
        },
        set (value) {
            formDef.currentItem.value = value
        }
    })

    const showCommentForm = ref(false);

    const cancel = () => {
        formDef.clearCurrentItem()
        formDef.clearErrors()
        showCommentForm.value = false;
        emits('canceled')
    }

    const save = () => {
        newComment.value.subject_type = props.subjectType,
        newComment.value.subject_id = group.value.id,
        newComment.value.metadata = {section: props.section}
        console.log(newComment.value)
        formDef.save(newComment.value)
            .then(comment => {
                comments.value.push(comment);
                emits('saved', comment);
                showCommentForm.value = false;
            })
    }
</script>
<template>
    <div>
        <div v-if="showCommentForm" class="bg-white p-2">
            <data-form :fields="fields" v-model="newComment" :errors="errors"></data-form>
            <button-row size="xs" submit-text="Save" @submitted="save" @canceled="cancel"></button-row>
        </div>
        <button v-else class="btn btn-xs block" @click="showCommentForm = true">Add comment</button>
    </div>
</template>