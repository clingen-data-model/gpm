import { ref, computed, watch } from 'vue'
import { formatDate } from '@/date_utils'
import commentRepository from '../repositories/comment_repository';

export default function (store) {
    const group = computed( () => {
        return store.getters['groups/currentItemOrNew'];
    });
    const expertPanel = computed( () => {
        return group.value.expert_panel;
    });
    const isGcep = computed( () => {
        return group.value.isGcep();
    });
    const isVcep = computed( () => {
        return group.value.isVcep();
    });
    const members = computed( () => {
        return group.value.members.map(m => ({
            id: m.id,
            name: m.person.name,
            institution: m.person.institution ? m.person.institution.name : null,
            credentials: m.person.credentials,
            expertise: m.expertise,
            roles: m.roles.map(r => r.name).join(', '),
            coi_completed: formatDate(m.coi_last_completed)
        }));
    });

    return {
        group,
        expertPanel,
        isGcep,
        isVcep,
        members,
    }
}
