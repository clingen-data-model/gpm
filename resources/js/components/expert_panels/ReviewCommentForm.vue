<script setup>
    import { computed, inject, onMounted } from 'vue';
    import formDefFactory from '../../forms/comment_form.js';

    const group = inject('group');

    const props = defineProps({
        commentManager: {
            type: Object,
            required: true
        },
        section: {
            type: [String, null],
            default: null
        },
        subjectType: {
            type: String,
            default: 'App\\Modules\\Group\\Models\\Group'
        },
        subjectId: {
            type: Number,
            required: false
        },
        comment: {
            type: Object,
        },
        onlyInternal: {
            type: Boolean,
            default: false
        }
    });

    onMounted(() => {
        if (props.comment) {
            formDef.currentItem.value = props.comment
        }
    })

    const emits = defineEmits(['saved', 'canceled']);

    const formDef = formDefFactory();

    const fields = computed(() => {
        const fields = [...formDef.fields.value];
        if (props.onlyInternal) {
            const typeFieldIdx = fields.findIndex(f => f.name == 'comment_type_id');
            fields.splice(typeFieldIdx, 1);
        }
        return fields;
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

    const cancel = () => {
        formDef.clearCurrentItem()
        formDef.clearErrors()
        emits('canceled')
    }

    const create = () => {
        newComment.value.subject_type = props.subjectType,
        newComment.value.subject_id = props.subjectId || group.value.id,
        newComment.value.metadata = {
            section: props.section,
            // This metadata is necessary to ensure reply comments are included in notifications.
            root_subject_type: 'App\\Modules\\Group\\Models\\Group',
            root_subject_id: group.value.id
        }

        if (props.onlyInternal) {
            newComment.value.comment_type_id = 1
        }
        formDef.save(newComment.value)
            .then(comment => {
                props.commentManager.addComment(comment);
                emits('saved', comment);
            })
    }

    const update = () => {
        if (props.onlyInternal) {
            newComment.value.comment_type_id = 1
        }
        formDef.update(newComment.value)
            .then(comment => {
                props.commentManager.updateComment(comment);
                emits('saved', comment);
            })
    }

    const save = () => {
        if (newComment.value.id) {
            update();
            return;
        }
        create();
    }
</script>
<template>
    <div>
        <data-form :fields="fields" v-model="newComment" :errors="errors"></data-form>
        <button-row size="xs" submit-text="Save" @submitted="save" @canceled="cancel"></button-row>
    </div>
</template>
