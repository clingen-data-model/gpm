import Person from '@/domain/person.js'
import {isText} from '@vue/compiler-core'
import {expect} from 'chai'

describe('Person entity', () => {
    it('can tell if it matches a keyword', () => {
        const p1 = new Person();
        expect(p1.matchesKeyword('bob')).to.be.false

        const p2 = new Person({first_name: 'bobby', last_name: 'dobbs', email: 'bob@dobbs.com'});
        expect(p2.matchesKeyword('bobby')).to.be.true
        expect(p2.matchesKeyword('dobbs')).to.be.true
        expect(p2.matchesKeyword('bob@dobbs')).to.be.true
        expect(p2.matchesKeyword('bobby dobbs')).to.be.true
    })
})