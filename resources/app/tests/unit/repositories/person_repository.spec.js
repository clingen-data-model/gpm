import PersonRepository from '@/adapters/person_repository.js'

describe('PersonRepository', () => {
    beforeEach(() => {
        PersonRepository.clear();
    });

    it('adds a person', () => {
        const newPerson = {
            email: 'adam.ant@rock.net',
            birthday: '1955-01-01',
            name: 'Jordie'
        }
        PersonRepository.add(newPerson);

        expect(PersonRepository.people.length).toBe(1)
        expect(PersonRepository.people).toContain(newPerson)
        expect(PersonRepository.people[PersonRepository.people.length - 1].id).toBe(PersonRepository.people.length);
    });

    it('overwrites a person with updated info', () => {
        const newPerson = {
            email: 'adam.ant@rock.net',
            birthday: '1955-01-01',
            name: 'Jordie'
        }
        PersonRepository.add(newPerson)

        const aNewPerson = {...newPerson };

        aNewPerson.name = 'adam';

        PersonRepository.add(aNewPerson);

        expect(PersonRepository.find(aNewPerson.id).name).toBe('adam')
    })

    it('retrieves a person with an id', () => {
        const newPerson = {
            email: 'adam.ant@rock.net',
            birthday: '1955-01-01',
            name: 'Jordie'
        }
        PersonRepository.add(newPerson);

        expect(PersonRepository.find(PersonRepository.people.length)).toBe(newPerson);
    })

    it('returns all people', () => {
        const newPerson = {
            email: 'adam.ant@rock.net',
            birthday: '1955-01-01',
            name: 'Jordie'
        }
        const geneBelcher = {
            email: 'gene@bobsburgers.com',
            birthday: '2010-01-01',
            name: 'Gene'
        };
        PersonRepository.add(newPerson);
        PersonRepository.add(geneBelcher);

        expect(PersonRepository.all()).toContain(newPerson)
        expect(PersonRepository.all()).toContain(geneBelcher)
    })

    it('removes people from the repository', () => {
        const newPerson = {
            email: 'adam.ant@rock.net',
            birthday: '1955-01-01',
            name: 'Jordie'
        }
        const geneBelcher = {
            email: 'gene@bobsburgers.com',
            birthday: '2010-01-01',
            name: 'Gene'
        };
        PersonRepository.add(newPerson);
        PersonRepository.add(geneBelcher);
        const lastLength = PersonRepository.people.length;

        PersonRepository.remove(geneBelcher.id);

        expect(PersonRepository.people.length).toBe(lastLength - 1);
    });
})