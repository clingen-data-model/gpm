import Group from '@/domain/group'
import config from '@/configs.json';

// const group = new Group({name: 'Bob\'s Burgers', uuid: 'abcd-1234', id: 1, group_type_id: config.groups.types.ep.id});

// group.addMember({
//     id: 1,
//     person_id: 1,
//     person: {
//         first_name: 'Bob',
//         last_name: 'Belcher',
//         email: 'bob@bobsburgers.com',
//     },
//     start_date: '2021-01-01T00:00:00Z',
//     end_date: null,
//     v1_contact: 1,
//     roles: [
//         { name: 'Chair', id: 2 },
//         { name: 'Coordinator', id: 1 },
//         { name: 'Biocurator', id: 3 }
//     ],
//     coi_last_completed: '2021-09-16T13:12:00Z',
//     training_completed_at: null,
//     needs_training: true
// });
// group.addMember({
//     id: 2,
//     person_id: 2,
//     person: {
//         first_name: 'Linda',
//         last_name: 'Belcher',
//         email: 'linda@bobsburgers.com',
//     },
//     start_date: '2021-01-01T00:00:00Z',
//     end_date: null,
//     v1_contact: 1,
//     roles: [
//         { name: 'Coordinator', id: 1 },
//         { name: 'parent', id: 16 }
//     ],
//     coi_last_completed: '2020-05-17T15:09:00Z',
//     training_completed_at: null,
//     needs_training: false
// });
// group.addMember({
//     id: 3,
//     person_id: 3,
//     person: {
//         first_name: 'Tina',
//         last_name: 'Belcher',
//         email: 'tina@bobsburgers.com',
//     },
//     start_date: '2021-01-01T00:00:00Z',
//     end_date: null,
//     v1_contact: 1,
//     roles: [
//         { name: 'kid', id: 17 },
//     ],
//     training_completed_at: '2020-09-19T00:00:00Z',
//     needs_training: true
// });
// group.addMember({
//     id: 4,
//     person_id: 4,
//     person: {
//         first_name: 'Gene',
//         last_name: 'Belcher',
//         email: 'gene@bobsburgers.com',
//     },
//     start_date: '2021-01-01T00:00:00Z',
//     end_date: null,
//     v1_contact: 1,
//     roles: [
//         { name: 'Biocurator', id: 3 },
//     ],
//     training_completed_at: null,
//     needs_training: true
// });

const group = {
    name: 'Bob\'s Burgers', 
    uuid: 'abcd-1234', 
    group_type_id: config.groups.types.ep.id,
    id: 1, 
    members: [
        {
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
                { name: 'Chair', id: 2 },
                { name: 'Coordinator', id: 1 },
                { name: 'Biocurator', id: 3 }
            ],
            coi_last_completed: '2021-09-16T13:12:00Z',
            training_completed_at: null,
            needs_training: true
        },
        {
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
                { name: 'Coordinator', id: 1 },
                { name: 'parent', id: 16 }
            ],
            coi_last_completed: '2020-05-17T15:09:00Z',
            training_completed_at: null,
            needs_training: false
        },
        {
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
                { name: 'Biocurator', id: 3 },
            ],
            training_completed_at: '2020-09-19T00:00:00Z',
            needs_training: true
        },
        {
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
                { name: 'Biocurator', id: 3 },
            ],
            permissions: [
                { name: 'Invite Members', id: 2},
                { name: 'Retire Members', id: 3},
            ],
            training_completed_at: null,
            needs_training: true
        }
    ]
}

console.log('bobs_burgers.group.members', group.members)

export default group;
