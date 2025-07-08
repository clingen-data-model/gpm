import {ref} from 'vue'
import is_validation_error from '@/http/is_validation_error'
import api from '@/http/api'

export const errors = ref({});
const editing = ref(false);
const hideForm = () => {
    editing.value = false;
    errors.value = {};
}
export const submitFormData = async ({method, url, data}) => {
    try {
        // return await api.put(
        return await api({
            method,
            url,
            data,
            headers: {
                'Content-Type': 'multipart/form-data'
            },
        }).then(response => response.data.data)
    } catch (error) {
        if (is_validation_error(error)) {
            errors.value = {...errors, ...error.response.data.errors}
        }
    }
}

export const resetErrors = () => {
    errors.value = {};
}

export default () => {
    return {
        errors,
        editing,
        hideForm,
        submitFormData,
        resetErrors,
    }
}
