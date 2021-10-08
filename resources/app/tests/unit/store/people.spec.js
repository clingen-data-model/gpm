import { expect } from 'chai'
import {getters, mutations, actions} from '@/store/people'
import { testAction } from '../test_utils'
import api from '@/http/api'

import Person from '../../../src/domain/person';

describe('people store getters', () => {
    const state = {
        items: [
            { uuid: 1234, email: 'morty@portal.com' },
            { uuid: 4567, email: 'rick@citadel.rick' }
        ]
    }
    it('personWithUuid returns item in state.items with uuid', () => {
        const person = getters.getPersonWithUuid(state, 4567)
        expect(person).to.eql({uuid: 4567,email: 'rick@citadel.rick'})
    });

    it('indexForPersonWithUuid returns index of person in state.items', () => {
        const idx = getters.indexForPersonWithUuid(state, 1234);
        expect(idx).to.equal(0)

        const idx2 = getters.indexForPersonWithUuid(state, 4567);
        expect(idx2).to.equal(1)
    })
})

describe('people store mutations', () => {
    it('addItem adds new people to its items', () => {
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

        expect(state.items.length).to.equal(0)
        mutations.addItem(state, personData);

        expect(state.items.length).to.equal(1);
        expect(state.items[0]).to.eql(new Person(personData));
    });

    it('addItem replaces an item with the same uuid', () => {
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

        mutations.addItem(state, personData);

        expect(state.items.length).to.equal(1)
        expect(state.items[0]).to.eql(new Person(personData))
    })
})

describe('people store actions', () => {

    it('it can create a person and call addItem mutation', async () => {
        let body = {};
        let url = '';
        const data =  {
            uuid: 123456,
            first_name: 'bob',
            last_name: 'dobs',
            email: 'bob@dobbs.com',
            phone: '555-555-5555'
        }

        const personData = {...data};
        personData.id = 666;
        const MockAdapter = require("axios-mock-adapter");
        const mock = new MockAdapter(api);

        mock.onPost("/api/people", data).reply(200, personData);

        testAction(
            actions.createPerson, 
            data, 
            {items: []}, 
            [
                { type: 'addPerson', payload: personData}
            ]
        )
    });
});