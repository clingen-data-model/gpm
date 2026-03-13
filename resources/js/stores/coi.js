import { defineStore } from 'pinia'
import application_repository from '@/adapters/application_repository'
import api from '@/http/api'

export const useCoiStore = defineStore('coi', {
    state: () => ({}),
    actions: {
        async storeCoi({ code, groupMemberId, coiData }) {
            const data = { ...coiData, group_member_id: groupMemberId }
            await api.post(`/api/coi/${code}`, data)
        },

        async storeLegacyCoi({ application, coiData }) {
            const document = await application_repository.addDocument(application, coiData)
            const data = {
                document_uuid: document.uuid,
                download_url: document.download_url,
            }
            await this.storeCoi({ code: application.coi_code, coiData: data })

            // Refresh the application in the applications store
            const { useApplicationsStore } = await import('./applications')
            useApplicationsStore().getApplication(application.uuid)
        },
    },
})
