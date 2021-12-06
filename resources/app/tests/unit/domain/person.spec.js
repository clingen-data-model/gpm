import {Person} from '@/domain/index'
import {expect} from 'chai'


const p1 = new Person({
    first_name: 'harry',
    last_name: 'potter',
    memberships: [
        {
            id: 1,
            cois: [],
            coi_needed: true,
            group: {
                id: 1, 
                name: 'Early Dog VCEP',
                group_type_id: 3,
            }
        },
        {
            id: 2,
            cois: [{blah: 'blah'}],
            coi_needed: false,
            coi_last_completed: '2020-01-01T10:00:00Z'
        }
    ]
});

describe('Person entity', () => {
    it('can tell if it matches a keyword', () => {
        expect(p1.matchesKeyword('bob')).to.be.false

        const p2 = new Person({first_name: 'bobby', last_name: 'dobbs', email: 'bob@dobbs.com'});
        expect(p2.matchesKeyword('bobby')).to.be.true
        expect(p2.matchesKeyword('dobbs')).to.be.true
        expect(p2.matchesKeyword('bob@dobbs')).to.be.true
        expect(p2.matchesKeyword('bobby dobbs')).to.be.true
    });

    it('can get memberships with completed cois', () => {

        expect(p1.hasCompletedCois).to.be.true;
        expect(p1.membershipsWithCompletedCois[0].id).to.equal(p1.memberships[1].id);
    });

    it('can get memberships with pending cois', () => {
        expect(p1.hasPendingCois).to.be.true;
        expect(p1.membershipsWithPendingCois[0].id).to.equal(p1.memberships[0].id)
    })
})