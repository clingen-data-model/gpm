import {expect} from 'chai'
import configs from '@/configs'
import {requirements, Group, GroupMember} from '@/domain'

const {groups} = configs;

let testGroup = new Group();
describe('minimumBiocurators', () => {
    beforeEach(() => {
        testGroup = new Group();
    });

    it('is not met when the group has zero trained biocurators', () => {
        expect(requirements.minimumBiocurators.isMet(testGroup)).to.be.false;

        testGroup.addMember(new GroupMember({
            roles: [
                groups.roles.biocurator
            ],
            training_level_1: true,
            training_level_2: false,
        }));

        expect(requirements.minimumBiocurators.isMet(testGroup)).to.be.false;
    });

    it('is met when the group has at least one biocurator', () => {

        testGroup.addMember(new GroupMember({
            roles: [
                groups.roles.biocurator
            ],
            training_level_1: true,
            training_level_2: true,
        }))

        expect(requirements.minimumBiocurators.isMet(testGroup)).to.be.true;
    });
});

describe('biocuratorTrainers', () => {
    beforeEach(() => {
        testGroup = new Group();
    });

    it('is not met when the group has zero biocurator trainers', () => {
        expect(requirements.biocuratorTrainers.isMet(testGroup)).to.be.false;
    });

    it('is met when the group has at least one biocurator trainer', () => {
        testGroup.addMember(new GroupMember({
            roles: [
                groups.roles['biocurator-trainer']
            ]
        }));

        expect(requirements.biocuratorTrainers.isMet(testGroup)).to.be.true;
    });
});

describe('coreApprovalMembers', () => {
    beforeEach(() => {
        testGroup = new Group();
    });

    it('is not met when the group has zero core approval members', () => {
        expect(requirements.coreApprovalMembers.isMet(testGroup)).to.be.false;
    });

    it('is met when the group has at least one core approval member', () => {
        testGroup.addMember(new GroupMember({
            roles: [
                groups.roles['core-approval-member']
            ]
        }));

        expect(requirements.coreApprovalMembers.isMet(testGroup)).to.be.true;
    });
});