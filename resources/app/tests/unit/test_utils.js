import {expect} from "chai";

export const testAction = (action, actionPayload, state, expectedMutations) => {
    let count = 0;

    const commit = (commitType, commitData) => {
        if (!expectedMutations[count]) {
            throw new Error('Unexpected mutation "'+commitType+'" called.')
        }
        const expectedMutation = expectedMutations[count];

        expect(commitType).to.equal(expectedMutation.type);
        expect(commitData).to.deep.equal(expectedMutation.payload);

        count++;
    }

    action({commit, state }, actionPayload);

    if (expectedMutations.length === 0) {
        expect(count).to.equal(0);
    }
}