import {expect} from 'chai'

import {GcepRequirements, VcepRequirements, Group} from '@/domain'

const gcepReqs = new GcepRequirements();
describe('GcepRequirements', () => {
    it ('can get all requirements', () => {
        expect(gcepReqs.getRequirementsFor())
            .to
            .eql(Object.values(GcepRequirements.requirements).flat());
    });

    it ('can get requirements for a single section', () => {
        expect(gcepReqs.getRequirementsFor('basicInfo'))
            .to
            .eql(GcepRequirements.requirements.basicInfo);
    });

    it ('can get requirements for multiple sections', () => {
        expect(gcepReqs.getRequirementsFor(['basicInfo', 'membership']))
            .to
            .eql([
                ...GcepRequirements.requirements.basicInfo, 
                ...GcepRequirements.requirements.membership
            ]);
    });

    it ('knows if the group meets requirements for a section', () => {
        const group = new Group({
            name: 'bird cat group',
            expert_panel: {
                long_base_name: 'bird GCEP',
                short_base_name: ''
            }
        });

        expect(gcepReqs.meetsRequirements(group, 'basicInfo')).to.be.false;

        group.expert_panel.short_base_name = 'BRD';

        expect(gcepReqs.meetsRequirements(group, 'basicInfo')).to.be.true;
    });

    it ('gets messages and requirement met/unment', () => {
        const group = new Group({
            name: 'Bird group',
            expert_panel: {
                long_base_name: 'Birds GCEP',
                short_base_name: ''
            }
        });

        expect(gcepReqs.checkRequirements(group, 'basicInfo'))
            .to
            .eql([
                {
                    label: 'Long Base Name',
                    isMet: true
                },
                {
                    label: 'Short Base Name',
                    isMet: false
                }
            ])


    });
});