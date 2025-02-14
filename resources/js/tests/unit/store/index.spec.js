import { expect } from 'chai'
import { mutations } from '@/store/index'

const { addRequest, removeRequest } = mutations;

describe ('mutations', () => {
    it('Increments state,openRequests', () => {
        const state = {
            openRequests: 0
        };
        addRequest(state);

        expect(state.openRequests).to.equal(1)
    });

    it('Decrements state.openRequests', () => {
        const state = {
            openRequests: 3
        };
        removeRequest(state);

        expect(state.openRequests).to.equal(2)
    });
});