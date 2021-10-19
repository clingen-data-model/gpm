<template>
    <div>
        <header class="pb-4">
            <note>Groups</note>
            <h1>{{group.name}}</h1>
            <dictionary-row label="Chairs:">
                <template v-slot:label><strong>Chairs:</strong></template>
                <div v-if="group.chairs.length > 0">
                    {{group.chairs.map(c => c.person.name).join(', ')}}
                </div>
                <div class="text-gray-500" v-else>
                    None assigned
                </div>
            </dictionary-row>
            <dictionary-row label="Coordinators:">
                <template v-slot:label><strong>Coordinators:</strong></template>
                <div v-if="group.coordinators.length > 0">
                    <!-- <pre>{{group.coordinators}}</pre> -->
                    {{group.coordinators.map(c => c.person.name).join(', ')}}
                </div>
                <div class="text-gray-500" v-else>
                    None assigned
                </div>
            </dictionary-row>
        </header>
        <tabs-container>
            <tab-item label="Members">
                <member-list :group="group"></member-list>
            </tab-item>
            <tab-item label="Scope">
                Description of scope and gene list will go here
            </tab-item>
            <tab-item label="Ongoing Curation">
                Curation review process, Evidence Summaries, etc. will go here
            </tab-item>
            <tab-item label="Documents">
                Group documents will go here.
            </tab-item>
            <tab-item label="Log"></tab-item>
            <!--
                <tab-item label="Manuscripts"></tab-item>
                <tab-item label="Funding"></tab-item>
            -->
        </tabs-container>

        <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="this.$route.meta.title">
            <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
        </modal-dialog>
        <teleport to='#debug-info'>
            <note>group.id: {{group.id}}</note>
        </teleport>
    </div>
</template>
<script>
import MemberList from '@/components/groups/MemberList';
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'

export default {
    name: 'GroupDetail',
    components: {
        MemberList
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    setup (props) {
        const store = useStore();
        const showModal = ref(false);

        onMounted(async () => {
            await store.dispatch('groups/find', props.uuid)
                .then(() => {
                    store.commit('groups/setCurrentItemIdxByUuid', props.uuid)
                })
        });

        return {
            group: computed(() => {
                return store.getters['groups/currentItem'] || {}
            }),
            showModal
        }
    },
    watch: {
        $route() {
            this.showModal = this.$route.meta.showModal 
                                ? Boolean(this.$route.meta.showModal) 
                                : false;
        }
    },
    methods: {
        hideModal () {
            this.$router.replace({name: 'GroupDetail', params: {uuid: this.uuid}});
        },
        handleModalClosed (evt) {
            this.clearModalForm(evt);
            this.$router.push({name: 'GroupDetail', params: {uuid: this.uuid}});
        },
        clearModalForm () {
            if (typeof this.$refs.modalView.clearForm === 'function') {
                this.$refs.modalView.clearForm();
            }
        },
    },
    mounted() {
        this.showModal = Boolean(this.$route.meta.showModal)
    }
}
</script>