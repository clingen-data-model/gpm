<script>
import DocumentList from '@/components/documents/DocumentList.vue';
import { useGroupsStore } from '@/stores/groups';

export default {
    name: 'GroupDocuments',
    components: {
        DocumentList
    },
    props: {

    },
    setup() {
        const groupsStore = useGroupsStore();
        return { groupsStore };
    },
    data() {
        return {

        }
    },
    computed: {
        group: {
            get () {
                return this.groupsStore.currentItemOrNew
            }
        },
        displayDocuments () {
            return this.group.documents;
        },
        canManageDocuments () {
            return this.hasAnyPermission(['groups-manage', 'ep-applications-manage', ['info-edit', this.group]]);
        }
    },
    methods: {
        async updateDocument (updatedData) {
            if (!this.canManageDocuments) {
                return;
            }
            await this.groupsStore.updateDocument({group: this.group, document: updatedData});
        },
        async createDocument (newDocumentData) {
            if (!this.canManageDocuments) {
                return;
            }
            await this.groupsStore.addDocument({group: this.group, data: newDocumentData});
        },
        async deleteDocument (document) {
            if (!this.canManageDocuments) {
                return;
            }
            await this.groupsStore.deleteDocument({group: this.group, document});
        }
    }
}
</script>
<template>
  <div>
    <DocumentList
      :documents="displayDocuments"
      :document-updater="updateDocument"
      :document-creator="createDocument"
      :document-deleter="deleteDocument"
      :can-manage="canManageDocuments"
    />
  </div>
</template>