<template>
    <div>
        <member-list :group="group"></member-list>
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
            { name: 'chair', id: 1 },
            { name: 'coordinator', id: 2 },
            { name: 'parent', id: 3 }
        ],
        coi_last_completed: Date.parse('2021-09-16T13:12:00Z'),
        training_completed_at: null,
        needs_training: false
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
            { name: 'coordinator', id: 2 },
            { name: 'parent', id: 3 }
        ],
        coi_last_completed: new Date('2020-05-17T15:09:00Z'),
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
            { name: 'kid', id: 4 },
        ],
        training_completed_at: new Date('2020-09-19T00:00:00Z'),
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
            { name: 'kid', id: 4 },
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
        // group: {
        //     type: Group,
        //     required: true,
        // }
    },
    data () {
        return {
            group: group
        }
    }
}
</script>