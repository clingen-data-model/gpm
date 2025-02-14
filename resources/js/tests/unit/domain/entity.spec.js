import { expect } from 'chai';
import Entity from '@/domain/entity'


let entity = {};
const attributes = {
    a: 1, b: 2, c: [1, 2, 3], 
    d: {
        ape: 'chimp', 
        bat: 'fruit'}, 
        created_at: null, 
        updated_at: null, 
        deleted_at: null
    };
describe('entity', () => {
    beforeEach(function () {
        entity = new Entity(attributes);
    });

    it('knows if attributes HAVE NOT been updated', () => {
        expect(entity.isDirty()).to.be.false;
    });

    it('knows if attributes HAVE been updated', () => {
        entity.a = 500;
        expect(entity.isDirty()).to.be.true;
    });

    it('knows if a particular attribute is dirty', () => {
        entity.a = 500;
        entity.c.push(4);
        entity.d.cat = 'bad';
        expect(entity.isDirty('a')).to.be.true;
        expect(entity.isDirty('b')).to.be.false;
        expect(entity.isDirty('c')).to.be.true;
        expect(entity.isDirty('d')).to.be.true;

        expect(entity.original.c).to.be.eq(attributes.c);
        expect(entity.original.d).to.be.eq(attributes.d);
    });

    it('can get original attribute values', () => {
        entity.a = 500;
        entity.b = 600;

        expect(entity.getOriginal()).to.eql(attributes);
    });

    it('can get a particular attribute\'s original value', () => {
        entity.a = 500;
        entity.b = 600;

        expect(entity.getDirty('b')).to.eql({b: {original: 2, new: 600}});
    });

    it('can revert all changed attributes to original values', () => {
        entity.a = 500;

        expect(entity.a).to.equal(500);
        
        entity.revertDirty();

        expect(entity.getDirty()).to.eql({});
        expect(entity.attributes.a).to.equal(1);
        expect(entity.attributes.b).to.equal(2);
    });
});