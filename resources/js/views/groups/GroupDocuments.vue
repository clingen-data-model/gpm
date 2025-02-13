<template>
    <div>
        <document-list :documents="displayDocuments"
            :documentUpdater="updateDocument"
            :documentCreator="createDocument"
            :documentDeleter="deleteDocument"
            :canManage="canManageDocuments"
        >
        </document-list>
    </div>
</template>
<script>
import DocumentList from '@/components/documents/DocumentList.vue';

export default {
    name: 'GroupDocuments',
    components: {
        DocumentList
    },
    props: {
        
    },
    data() {
        return {
            
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew']
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
                this.$store.pushError('Permission denied');
                return;
            }
            await this.$store.dispatch(
                'groups/updateDocument', 
                {group: this.group, document: updatedData}
            );
        },
        async createDocument (newDocumentData) {
            if (!this.canManageDocuments) {
                this.$store.pushError('Permission denied');
                return;
            }
            await this.$store.dispatch(
                'groups/addDocument', 
                {group: this.group, data: newDocumentData}
            );
        },
        async deleteDocument (document) {
            if (!this.canManageDocuments) {
                this.$store.pushError('Permission denied');
                return;
            }
            await this.$store.dispatch(
                'groups/deleteDocument', 
                {group: this.group, document}
            );
        }
    }
}
</script>