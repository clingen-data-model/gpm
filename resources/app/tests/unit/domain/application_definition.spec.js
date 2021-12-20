import { expect } from 'chai'
import {
    ApplicationSection, 
    ApplicationStep, 
    ApplicationDefinition,
    applicationDefinitionFactory
} from '@/domain/';
import {Requirement} from '@/domain/ep_requirements'

const testReq1 = new Requirement('testReq1', () => true);
const testReq2 = new Requirement('testReq2', () => true);
const testReq3 = new Requirement('testReq3', () => true);
const testReq4 = new Requirement('testReq4', () => true);
const testReqFail = new Requirement('testReqFail', () => false);

let testSection1 = null;
describe ('ApplicationSection', () => {
    beforeEach(() => {
        testSection1 = new ApplicationSection(
            'test', 
            'Test!', 
            [testReq1, testReq2],
            []
        );        
    });
    
    it ('can be instantiated', () => {
        expect(testSection1).to.be.instanceOf(Object);
    });

    it('knows if it has requirements', () => {
        expect(testSection1.hasRequirements).to.be.true;
        
        testSection1.requirements = [];
        expect(testSection1.hasRequirements).to.be.false;
    });

    it ('can evaluate its requirements', () => {
        testSection1.requirements = [testReq1, testReqFail]
        expect(testSection1.evaluateRequirements({}))
            .to.be.eql([
                {label: 'testReq1', isMet: true},
                {label: 'testReqFail', isMet: false},
            ])
    });

    it ('knows whether requirements are met', () => {
        expect(testSection1.meetsRequirements({})).to.be.true;

        testSection1.requirements = [testReq1, testReqFail]
        expect(testSection1.meetsRequirements({})).to.be.false;
    });

    it('knows which requirements are met', () => {
        testSection1.requirements = [testReq1, testReqFail]
        expect(testSection1.metRequirements({})).to.eql([testReq1]);
    });

    it('knows which requirements are not met', () => {
        testSection1.requirements = [testReq1, testReqFail]
        expect(testSection1.unmetRequirements({})).to.eql([testReqFail]);
    });
});

let testSection2 = null;
let testStep = null;
describe ('ApplicationStep', () => {
    beforeEach(() => {
        testSection1 = new ApplicationSection(
            'testSection1', 
            'Test!', 
            [testReq1, testReq2],
            []
        );        
        testSection2 = new ApplicationSection(
            'testSection2', 
            'Test!', 
            [testReq3],
            []
        );  
        testStep = new ApplicationStep(
                        'testStep1',
                        [testSection1, testSection2],
                        'Test Step 1',
                        (group) => {
                            return group > 3
                        },
                        (group) => {
                            return group > 3
                        }
                    );
    });

    it('can be instantiated', () => {
        expect(testStep).to.be.instanceOf(ApplicationStep)
    });

    it('knows if it\'s sections have requirements', () => {
        expect(testStep.hasRequirements).to.be.true;

        testSection1.requirements = [];
        testSection2.requirements = [];

        expect(testStep.hasRequirements).to.be.false;
    });

    it ('can get it\'s sections requirements', () => {
        expect(testStep.requirements).to.be.eql([
            ...testSection1.requirements,
            ...testSection2.requirements
        ]);
    });

    it('can evaluate all section requirements', () => {
        testSection1.requirements = [testReq1, testReqFail];
        expect(testStep.evaluateRequirements({}))
            .to.eql([
                {label: 'testReq1', isMet: true},
                {label: 'testReqFail', isMet: false},
                {label: 'testReq3', isMet: true},
            ]);
    });

    it('knows if all sections meet requirements', () => {
        expect(testStep.meetsRequirements({})).to.be.true

        testSection2.requirements = [testReqFail];
        expect(testStep.meetsRequirements({})).to.be.false
    });

    it('knows which requirements are met', () => {
        testSection2.requirements = [testReqFail];

        expect(testStep.metRequirements({})).to.eql([testReq1, testReq2]);
    })

    it('knows which requirements are not met', () => {
        testSection2.requirements = [testReqFail];

        expect(testStep.unmetRequirements({})).to.eql([testReqFail]);
    })

    it ('has getters for sections by their names.', () => {
        expect(testStep.testSection1).to.eql(testSection1);
        expect(testStep.testSection2).to.eql(testSection2);
    });

    it ('can evaluate whether it is complete for a group', () => {
        expect(testStep.isComplete(3)).to.be.false;
        expect(testStep.isComplete(4)).to.be.true;
    })

    it ('can evaluate whether it is disabled for a group', () => {
        expect(testStep.isDisabled(3)).to.be.false;
        expect(testStep.isDisabled(4)).to.be.true;
    })

});

let testSection3 = null;
let testStep2 = null;
let testApp = null;
describe ('AppliationDefinition', () => {
    beforeEach(() => {
        testSection1 = new ApplicationSection( 'test section 1', 'Test section 1!',  [testReq1, testReq2], [] );
        testSection2 = new ApplicationSection( 'test section 2', 'Test!', [testReq3], [] );  
        testStep = new ApplicationStep('testStep1', [testSection1, testSection2], 'Test Step 1');
        
        testSection3 = new ApplicationSection('test section 3', 'Test Section 3!!', [testReq4], []);
        testStep2 = new ApplicationStep('testStep2', [testSection3], 'Test Step 2');

        testApp = new ApplicationDefinition([testStep, testStep2]);
    });

    it ('can get it\'s sections', () => {
        expect(testApp.sections).to.eql([testSection1, testSection2, testSection3]);
    });

    it ('can get all requirements from step section', () => {
        expect(testApp.requirements).to.eql([testReq1, testReq2, testReq3, testReq4]);
    });

    it ('can get the step by 1-indexed numeric value', () => {
        expect(testApp.getStep(1).name).to.equal('testStep1');
    });

    it ('can get a step by it\'s name', () => {
        expect(testApp.getStep('testStep1').name).to.eql('testStep1');
    });

    it ('has getters for steps by their names.', () => {
        expect(testApp.testStep1).to.eql(testStep);
        expect(testApp.testStep2).to.eql(testStep2);
    });
})

describe('AppliationDefinitionFactory', () => {
    it('can build an application from an object', () => {
        const def = {
            steps: {
                step1: {
                    name: 'Step 1',
                    sections: {
                        section1: {
                            name: 'Section 1',
                            requirements: [
                                testReq1,
                                testReq2
                            ]
                        },
                        section2: {
                            name: 'Section 2',
                            requirements: [
                                testReq3
                            ]
                        }
                    }
                },
                step2: {
                    name: 'Step 2',
                    sections: {
                        section3: {
                            name: 'Section 3',
                            requirements: [
                                testReq4
                            ]
                        }
                    }
                }
            }
        };

        const application = applicationDefinitionFactory(def);

        expect(application.steps.length).to.equal(2);
        expect(application.steps[0].sections.length).to.equal(2);
        expect(application.steps[0].name).to.equal('step1');
        expect(application.steps[0].title).to.equal('Step 1');
        expect(application.steps[1].sections[0].requirements).to.eql([testReq4])
        expect(application.steps[1].sections[0].requirements).to.eql([testReq4])
    })
})