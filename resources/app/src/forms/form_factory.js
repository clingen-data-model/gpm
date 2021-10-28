import {ref} from 'vue'

const errors = ref({});
const editing = ref(false);
const hideForm = () => {
    console.log('hideForm');
    editing.value = false;
    errors.value = {};
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
        }
    }
}