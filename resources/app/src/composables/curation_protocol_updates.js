import {ref, watch} from 'vue'
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error'

export const protocolId =  ref(null);
export const protocolOther =  ref(null);
export const errors =  ref({});
export const meetingFrequency =  ref({});

export const clearErrors = () => {
    console.log('clearErrors')
    errors.value = {}
};

export const syncData =  (group) => {
    protocolId.value = group.expert_panel.curation_review_protocol_id;
    protocolOther.value = group.expert_panel.curation_review_protocol_other;
    meetingFrequency.value = group.expert_panel.meeting_frequency;
};

export const saveProtocolData = async (group) => {
    try {
        clearErrors();
        if (protocolId.value != 100) {
            protocolOther.value = null;
        }
        await api.put(`/api/groups/${group.uuid}/application/curation-review-protocols`, {
            curation_review_protocol_id: protocolId.value,
            curation_review_protocol_other: protocolOther.value,
            meeting_frequency: meetingFrequency.value,
            expert_panel_type_id: group.expert_panel.expert_panel_type_id,
        });
    } catch (error) {
        if (is_validation_error(error)) {
            errors.value = error.response.data.errors;
        }
        throw error;
    }
}