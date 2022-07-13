import {ref} from 'vue'
import BaseEntityForm from "./base_entity_form.js";
import {CommentRepository, CommentTypeRepository} from '@/repositories/comment_repository.js'

export const commentTypes = ref([]);
export const getCommentTypes = async () => {
    return await CommentRepository.query()
            .then(data => {
                commentTypes.value = data;
                return data;
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
        label: 'Reference Code',
        type: 'large-text',
        required: true,
    }
]);

export default (new BaseEntityForm(fields, CommentTypeRepository))