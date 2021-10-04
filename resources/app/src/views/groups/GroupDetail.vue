<template>
    <div>
        <member-list :group="group"></member-list>

        <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="this.$route.meta.title">
            <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
        </modal-dialog>
        <note>group.id: {{group.id}}</note>
    </div>
</template>
<script>
    import Group from "@/domain/group";
    import config from '@/configs.json';
    import MemberList from '@/components/groups/MemberList';

    const group = new Group({name: 'Bob\'s Burgers', uuid: 'abcd-1234', id: 1, group_type_id: config.groups.types.ep.id});
    group.addMember({
        id: 1,
        person_id: 1,
        person: {
            first_name: 'Bob',
            last_name: 'Belcher',
            email: 'bob@bobsburgers.com',
        },
        start_date: '2021-01-01T00:00:00Z',
        end_date: null,
        v1_contact: 1,
        roles: [
            { name: 'chair', id: 2 },
            { name: 'coordinator', id: 1 },
            { name: 'biocurator', id: 3 }
        ],
        coi_last_completed: '2021-09-16T13:12:00Z',
        training_completed_at: null,
        needs_training: true
    });
    group.addMember({
        id: 2,
        person_id: 2,
        person: {
            first_name: 'Linda',
            last_name: 'Belcher',
            email: 'linda@bobsburgers.com',
        },
        start_date: '2021-01-01T00:00:00Z',
        end_date: null,
        v1_contact: 1,
        roles: [
            { name: 'coordinator', id: 1 },
            { name: 'parent', id: 16 }
        ],
        coi_last_completed: '2020-05-17T15:09:00Z',
        training_completed_at: null,
        needs_training: false
    });
    group.addMember({
        id: 3,
        person_id: 3,
        person: {
            first_name: 'Tina',
            last_name: 'Belcher',
            email: 'tina@bobsburgers.com',
        },
        start_date: '2021-01-01T00:00:00Z',
        end_date: null,
        v1_contact: 1,
        roles: [
            { name: 'kid', id: 17 },
        ],
        training_completed_at: '2020-09-19T00:00:00Z',
        needs_training: true
    });
    group.addMember({
        id: 4,
        person_id: 4,
        person: {
            first_name: 'Gene',
            last_name: 'Belcher',
            email: 'gene@bobsburgers.com',
        },
        start_date: '2021-01-01T00:00:00Z',
        end_date: null,
        v1_contact: 1,
        roles: [
            { name: 'biocurator', id: 3 },
        ],
        training_completed_at: null,
        needs_training: true
    });


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
    data () {
        return {
            group: group,
            showModal: false,
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
        console.log(this.$options.name)
        this.showModal = Boolean(this.$route.meta.showModal)
    }
}
</script>