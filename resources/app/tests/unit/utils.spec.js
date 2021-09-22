import {titleCase} from '@/utils'
import {expect} from 'chai'

describe('titleCase', () => {
    it('upper cases the first letter of each word in a string', () => {
        const string = 'it\'s a sad parade';
        const titleCased = titleCase(string);
        expect(titleCased).to.equal('It\'s A Sad Parade');
    })
})