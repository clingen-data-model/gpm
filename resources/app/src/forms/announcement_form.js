import {ref, computed} from 'vue';
import {api, isValidationError} from '@/http';

export const announcement = ref({type: 'info', message: null});
export const validationErrors = ref({});

export const fields = [
    {
        name: 'roles',
        placeholder: 'coordinator, admin'
    },
    {
        name: 'type',
        label: 'variant',
        type: 'select', 
        options: [
            {value: 'info', label: 'info (blue)'}, 
            {value: 'success', label: 'success (green)'},
            {value: 'warning', label: 'warning (yellow)'},
            {value: 'danger', label: 'danger (red)'},
            {value: 'bland', label: 'bland (gray)'},
        ]
    },
    {
        name: 'message',
        type: 'large-text',
        placeholder: 'You message text in **markdown**'
    }
];
export const saveAnnouncement = () => {
    return api.post('/api/announcements', announcement.value)
        .then(response => {
            return response;
        })
        .catch (error => {
            if (isValidationError(error)) {
                validationErrors.value = error.response.data.errors;
                return;
            }
            throw error;
        });
}

export const cancelAnnouncement = () => {
    announcement.value = {};
}