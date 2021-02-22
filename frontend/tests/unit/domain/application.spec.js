import Application from '../../../src/domain/application';

describe('application entity', () => {
    test('it can be instantiated', () => {
        const application = new Application();
        expect(application).toBeInstanceOf(Application)
    });

    test('it adds a getter for each attribute', () => {
        const app = new Application({working_name: 'test working name'});
        expect(app.working_name).toBe('test working name');
        expect(app.cdwg).toEqual({});
    });

    test('it gets attr as a date if in the dates list', () => {
        const app = new Application({date_initiated: '2021-01-01'});

        const date = new Date(Date.parse('2021-01-01'));

        expect(app.date_initiated).toEqual(date);
    });

    test('it adds a setter for each attribute', () => {
        const app = new Application();
        app.working_name = 'Bob\'s Burgers';
        expect(app.working_name).toBe('Bob\'s Burgers');
        expect(app.attributes.working_name).toBe('Bob\'s Burgers')
    });

    test('it knows if it is a completed aplication', () => {
        const app = new Application();
        expect(app.isCompleted).toBe(false)
       
        app.date_completed = '2021-02-01';
        expect(app.isCompleted).toEqual(true);
    });

    test('it knows if it is for a gcep or a vcep', () => {
        const gcep = new Application({
            ep_type_id: 1,
        });
        expect(gcep.isGcep).toEqual(true)

        const vcep = new Application({
            ep_type_id: 2,
        });
        expect(vcep.isVcep).toEqual(true)
    })

    test('it supports adding of additional attributes after instantiation', () => {
        const app = new Application();
        app.setAttribute('working_name', 'tester')
        app.setAttribute('beans', true)
        expect(app.attributes.beans).toEqual(true);
        expect(app.working_name).toBe('tester')
    })

    test('it knows how many steps it should have', () => {
        const gcep = new Application({
            ep_type_id: 1,
        });
        expect(gcep.steps).toEqual([1])

        const vcep = new Application({
            ep_type_id: 2,
        });
        expect(vcep.steps).toEqual([1,2,3,4])

    })

    test('it knows if a given step is completed', () => {
        const app = new Application({
                            ep_type_id: 2,
                            approval_dates: {
                                'step 1': '2021-01-01', 
                                'step 2': '2021-02-01'
                            }
                        });

        expect(app.stepIsApproved(1)).toEqual(true)
        expect(app.stepIsApproved(2)).toEqual(true)
        expect(app.stepIsApproved(3)).toEqual(false)
        expect(app.stepIsApproved(4)).toEqual(false)
    })

    test('it gets the first and final version for a given type of a document', () => {
        const app = new Application({
            documents: [
                {
                    "document_category_id": 3,
                    "version": 1,
                    "date_received": "2021-02-10T00:00:00.000000Z",
                    "date_reviewed": "2021-02-11T00:00:00.000000Z",
                },
                {
                    "document_category_id": 3,
                    "version": 2,
                    "date_received": "2021-02-10T00:00:00.000000Z",
                    "date_reviewed": "2021-02-11T00:00:00.000000Z",
                }
            ]
        });

        expect(app.firstDocumentOfType(3)).toEqual({
            "document_category_id": 3,
            "version": 1,
            "date_received": "2021-02-10T00:00:00.000000Z",
            "date_reviewed": "2021-02-11T00:00:00.000000Z",
        });

        expect(app.finalDocumentOfType(3)).toEqual({
            "document_category_id": 3,
            "version": 2,
            "date_received": "2021-02-10T00:00:00.000000Z",
            "date_reviewed": "2021-02-11T00:00:00.000000Z",
        });
    })

    test('it gets approval date for a given step', () => {
        const app = new Application({
            ep_type_id: 2,
            approval_dates: {
                'step 1': '2021-01-01', 
                'step 2': '2021-02-01'
            }
        });

        expect(app.approvalDateForStep(1)).toEqual(new Date(Date.parse('2021-01-01')));
        expect(app.approvalDateForStep(2)).toEqual(new Date(Date.parse('2021-02-01')));
        expect(app.approvalDateForStep(3)).toEqual(null);
    });

    test('it can clone itself', () => {
        const app = new Application({working_name: 'turts'});
        const clone = app.clone();

        expect(clone.working_name).toBe('turts');

        clone.working_name = 'tewtles';

        expect(app.working_name).toBe('turts');
        expect(clone.working_name).toBe('tewtles')
    })
})