import {ref} from 'vue'
import BaseEntityForm from "./base_entity_form.js";
import {commentRepository, typeRepository} from '@/repositories/comment_repository.js'

export const commentTypes = ref([]);
export const getCommentTypes = async () => {
    return await typeRepository.query()
            .then(items => {
                commentTypes.value = items.map(i => ({label: i.name, value: i.id}));
                return commentTypes.value;
            });
};

export const fields = ref([
    {
        name: 'comment_type_id',
        label: 'Type',
        type: 'select',
        options: commentTypes,
        required: true,
    },
    {
        name: 'content',
        label: 'Comment',
        type: 'large-text',
        required: true,
    },
]);

getCommentTypes();

export default (new BaseEntityForm(fields, commentRepository))