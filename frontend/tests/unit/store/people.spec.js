// eslint-disable-next-line
import store from '@/store/index'
import people from '@/store/people'
import axios from '@/http/api'
import Person from '../../../src/domain/person';

jest.mock("axios");

describe('people store getters', () => {
    const state = {
        items: [
            { uuid: 1234, email: 'morty@portal.com' },
            { uuid: 4567, email: 'rick@citadel.rick' }
        ]
    }
    test('personWithUuid returns item in state.items with uuid', () => {
        const person = people.getters.personWithUuid(state, 4567)
        expect(person).toEqual({uuid: 4567,email: 'rick@citadel.rick'})
    });

    test('indexForPersonWithUuid returns index of person in state.items', () => {
        const idx = people.getters.indexForPersonWithUuid(state, 1234);
        expect(idx).toBe(0)

        const idx2 = people.getters.indexForPersonWithUuid(state, 4567);
        expect(idx2).toBe(1)
    })
})

describe('people store mutations', () => {
    test('addItem adds new people to its items', () => {
        const state = {
            items: []
        }
        const personData = {
            uuid: 123456,
            first_name: 'bob',
            last_name: 'dobs',
            email: 'bob@dobbs.com',
            phone: '555-555-5555'
        }

        people.mutations.addItem(state, personData);

        expect(state.items.length).toEqual(1)
        expect(state.items[0]).toEqual(new Person(personData))
    });

    test('addItem replaces an item with the same uuid', () => {
        const state = {
            items: [{
                uuid: 123456,
                first_name: 'bob',
                last_name: 'dobs',
                email: 'bob@dobbs.com',
                phone: '555-555-5555'
            }]
        }
        const personData = {
            uuid: 123456,
            first_name: 'Beth',
            last_name: 'Sanchez',
            email: 'beth@horse-doctor.com',
            phone: '555-555-5555'
        }

        people.mutations.addItem(state, personData);

        expect(state.items.length).toEqual(1)
        expect(state.items[0]).toEqual(new Person(personData))
    })
})

describe('people store actions', () => {
    test('it can create a person and call addItem mutation', async () => {
        let body = {};
        let url = '';
        const data =  {
            uuid: 123456,
            first_name: 'bob',
            last_name: 'dobs',
            email: 'bob@dobbs.com',
            phone: '555-555-5555'
        }
        axios.post.mockImplementationOnce(() => Promise.resolve({data: data}));

        const commit = jest.fn();

        const personData = {...data};

        await people.actions.createPerson( { commit }, personData);

        expect(commit).toHaveBeenCalledWith('addItem', data)
    });
});