<template>
    <div>
        <document-list :documents="displayDocuments"
            :documentUpdater="updateDocument"
            :documentCreator="createDocument"
            :documentDeleter="deleteDocument"
        >
        </document-list>
    </div>
</template>
<script>
import DocumentList from '@/components/documents/DocumentList';

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
        }
    },
    methods: {
        async updateDocument (updatedData) {
            await this.$store.dispatch(
                'groups/updateDocument', 
                {group: this.group, document: updatedData}
            );
        },
        async createDocument (newDocumentData) {
            await this.$store.dispatch(
                'groups/addDocument', 
                {group: this.group, data: newDocumentData}
            );
        },
        async deleteDocument (document) {
            await this.$store.dispatch(
                'groups/deleteDocument', 
                {group: this.group, document}
            );
        }
    }
}
</script>