import {expect} from "chai";

export const testAction = (action, actionPayload, state, expectedMutations) => {
    let count = 0;

    const commit = (commitType, commitData) => {
        if (!expectedMutations[count]) {
            throw new Error('test_utils.testAction error: Unexpected mutation "'+commitType+'" called.')
        }
        if (!expectedMutations[count].payload) {
            throw new Error('test_utils.testAction error: Expected payload in "expectedMutations" argument')
        }
        if (!expectedMutations[count].type) {
            throw new Error('test_utils.testAction error: Expected type in "expectedMutations" argument')
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