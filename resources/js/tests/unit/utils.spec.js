import {titleCase, normalizeCase, kebabCase, snakeCase, camelCase} from '@/string_utils.js'
import {expect} from 'chai'

describe('normalizeCase', () => {
    it ('Lowercases captial letters and adds a space between them', () => {
        expect(normalizeCase('TestMeToo')).to.equal('test me too');
    });

    it ('Replaces 2+ spaces with one space', () => {
        expect(normalizeCase('test  me      too')).to.equal('test me too');
    })

    it ('Replaces - with space', () => {
        expect(normalizeCase('test-me-too')).to.equal('test me too');
    })

    it ('Replaces _ with space', () => {
        expect(normalizeCase('test_me_too')).to.equal('test me too');
    })
})

describe('titleCase', () => {
    it('upper cases the first letter of each word in a string', () => {
        expect(titleCase('it\'s a sad parade')).to.equal('It\'s A Sad Parade');
    })
})

describe('snakeCase', () => {
    it('snake cases a string', () => {
        expect(snakeCase(' I am a-snek')).to.equal('i_am_a_snek');
    })
})

describe('kebabCase', () => {
    it('kebab cases a string', () => {
        expect(kebabCase(' I am a_snek')).to.equal('i-am-a-snek');
    })
})

describe('camelCase', () => {
    it('camel cases a string', () => {
        expect(camelCase(' I am a_snek')).to.equal('iAmASnek');
    })
})
