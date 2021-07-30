import store from '.'
import application_repository from '@/adapters/application_repository'
import api from '@/http/api'

export default {
    state: {},
    mutations: {},
    actions: {
        // eslint-disable-next-line
        async storeCoi ({commit}, {code, coiData}) {
            await api.post(`/api/coi/${code}`, coiData);
        },

        // eslint-disable-next-line
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