import store from '.'
import application_repository from '@/adapters/application_repository'
import api from '@/http/api'

export default {
    state: {},
    mutations: {},
    actions: {
        // eslint-disable-next-line unused-imports/no-unused-vars
        async storeCoi ({commit}, {code, groupMemberId, coiData}) {
            const data = {...coiData, ...{group_member_id: groupMemberId}};
            await api.post(`/api/coi/${code}`, data);
        },

        // eslint-disable-next-line unused-imports/no-unused-vars
        async storeLegacyCoi ({commit}, {application, coiData}) {
            await application_repository.addDocument(application, coiData)
                .then(async document => {
                    const data = {
                        document_uuid: document.uuid,
                        download_url: document.download_url
                    };
                    await store.dispatch('storeCoi', {code: application.coi_code, coiData: data});
                    store.dispatch('applications/getApplication', application.uuid)
                });
        }
    }
}