import {ref} from 'vue'
import BaseEntityForm from "./base_entity_form.js";
import {commentRepository, typeRepository} from '@/repositories/comment_repository.js'

console.log(typeRepository);

export const commentTypes = ref([]);
export const getCommentTypes = async () => {
    return await typeRepository.query()
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
    },
]);

getCommentTypes();

export default (new BaseEntityForm(fields, commentRepository))