import { expect } from 'chai';
import Entity from '@/domain/entity'


let entity = {};
describe('entity', () => {
    beforeEach(function () {
        entity = new Entity({a: 1, b: 2});
    });

    it('knows if attributes have been updated', () => {
        expect(entity.isDirty()).to.be.false;

        entity.a = 500;

        expect(entity.isDirty()).to.be.true;
    });

    it('knows if a particular attribute is dirty', () => {
        entity.a = 500;
        expect(entity.isDirty('a')).to.be.true;
        expect(entity.isDirty('b')).to.be.false;
    });

    it('can get original attribute values', () => {
        entity.a = 500;
        entity.b = 600;

        expect(entity.getDirty()).to.eql({a: {original: 1, new: 500}, b: {original: 2, new: 600}});
    });

    it('can get a particular attribute\'s original value', () => {
        entity.a = 500;
        entity.b = 600;

        expect(entity.getDirty('b')).to.eql({b: {original: 2, new: 600}});
    });

    it('can revert all changed attributes to original values', () => {
        entity.a = 500;
        
        entity.revertDirty();

        expect(entity.getDirty()).to.eql({});
        expect(entity.attributes.a).to.equal(1);
        expect(entity.attributes.b).to.equal(2);
    });
});