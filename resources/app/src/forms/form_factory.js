import {ref} from 'vue'
import is_validation_error from '@/http/is_validation_error'
import api from '@/http/api'

const errors = ref({});
const editing = ref(false);
const hideForm = () => {
    editing.value = false;
    errors.value = {};
}
const submitFormData = async ({method, url, data}) => {
    try {
        // return await api.put(
        return await api({
            method,
            url, 
            data
        }).then(response => response.data.data)
    } catch (error) {
        if (is_validation_error(error)) {
            errors.value = {...errors, ...error.response.data.errors}
        }
    }
}

export default (props, context) => {
    return {
        errors,
        editing,
        hideForm,
        showForm: () => {
            errors.value = {};
            editing.value = true;
            context.emit('editing');
        },
        cancel: () => {
            hideForm();
            context.emit('canceled');
        },
        submitFormData
    }
}