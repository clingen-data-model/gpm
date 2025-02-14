import { expect } from 'chai'
import Application from '@/domain/application';

describe('application entity', () => {
    it('it can be instantiated', () => {
        const application = new Application();
        expect(application).to.be.an.instanceof(Application)
    });

    it('it adds a getter for each attribute', () => {
        const app = new Application({working_name: 'test working name'});
        expect(app.working_name).to.equal('test working name');
        expect(app.cdwg).to.eql({});
    });

    it('it gets attr as a date if in the dates list', () => {
        const app = new Application({date_initiated: '2021-01-01'});

        const date = new Date(Date.parse('2021-01-01'));

        expect(app.date_initiated).to.eql(date);
    });

    it('it adds a setter for each attribute', () => {
        const app = new Application();
        app.working_name = 'Bob\'s Burgers';
        expect(app.working_name).to.equal('Bob\'s Burgers');
        expect(app.attributes.working_name).to.equal('Bob\'s Burgers')
    });

    it('it knows if it is a completed aplication', () => {
        const app = new Application();
        expect(app.isCompleted).to.equal(false)
       
        app.date_completed = '2021-02-01';
        expect(app.isCompleted).to.equal(true);
    });

    it('it supports adding of additional attributes after instantiation', () => {
        const app = new Application();
        app.setAttribute('working_name', 'tester')
        app.setAttribute('beans', true)
        expect(app.attributes.beans).to.equal(true);
        expect(app.working_name).to.equal('tester')
    })

    it('it knows how many steps it should have', () => {
        const gcep = new Application({
            expert_panel_type_id: 1,
        });
        expect(gcep.steps).to.eql([1])

        const vcep = new Application({
            expert_panel_type_id: 2,
        });
        expect(vcep.steps).to.eql([1,2,3,4])

    })

    it('it knows if a given step is completed', () => {
        const app = new Application({
                            expert_panel_type_id: 2,
                            step_1_approval_date: '2021-01-01',
                            step_2_approval_date: '2021-01-01',
                            step_3_approval_date: null,
                            step_4_approval_date: null,
                        });

        expect(app.stepIsApproved(1)).to.be.true
        expect(app.stepIsApproved(2)).to.be.true
        expect(app.stepIsApproved(3)).to.be.false
        expect(app.stepIsApproved(4)).to.be.false
    })

    it('it gets the first and final version for a given type of a document', () => {
        const app = new Application({
            documents: [
                {
                    "document_type_id": 3,
                    "version": 1,
                    "date_received": "2021-02-10T00:00:00.000000Z",
                    "date_reviewed": "2021-02-11T00:00:00.000000Z",
                },
                {
                    "document_type_id": 3,
                    "version": 2,
                    "date_received": "2021-02-10T00:00:00.000000Z",
                    "date_reviewed": "2021-02-11T00:00:00.000000Z",
                }
            ]
        });

        expect(app.firstDocumentOfType(3)).to.eql({
            "document_type_id": 3,
            "version": 1,
            "date_received": "2021-02-10T00:00:00.000000Z",
            "date_reviewed": "2021-02-11T00:00:00.000000Z",
        });

        expect(app.finalDocumentOfType(3)).to.eql({
            "document_type_id": 3,
            "version": 2,
            "date_received": "2021-02-10T00:00:00.000000Z",
            "date_reviewed": "2021-02-11T00:00:00.000000Z",
        });
    })

    it('it gets approval date for a given step', () => {
        const app = new Application({
            expert_panel_type_id: 2,
            step_1_approval_date: '2021-01-01', 
            step_2_approval_date: '2021-02-01'
        });

        expect(app.approvalDateForStep(1)).to.eql(new Date(Date.parse('2021-01-01')));
        expect(app.approvalDateForStep(2)).to.eql(new Date(Date.parse('2021-02-01')));
        expect(app.approvalDateForStep(3)).to.equal(null);
    });

    it('it can clone itself', () => {
        const app = new Application({working_name: 'turts'});
        const clone = app.clone();

        expect(clone.working_name).to.equal('turts');

        clone.working_name = 'tewtles';

        expect(app.working_name).to.equal('turts');
        expect(clone.working_name).to.equal('tewtles')
    })
})