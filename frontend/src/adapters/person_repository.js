const people = [{
        id: 1,
        name: 'Elenor',
        email: 'elenor@medplace.com',
        birthday: '1987-01-01'
    },
    {
        id: 2,
        name: 'Chidi',
        email: 'chidi@ethics.org',
        birthday: '1984-02-01'
    },
    {
        id: 3,
        name: 'Tehani',
        email: 'tehani@namedrop.com',
        birthday: '1990-02-01'
    },
    {
        id: 4,
        name: 'Jason',
        email: 'jason@stupidnickswinghut.com',
        birthday: '1992-02-01'
    }
];

function getNextId() {
    return people.length + 1;
}

function all() {
    return people;
}

function add(person) {
    if (person.id) {
        const personIndex = people.findIndex(item => item.id == person.id);
        if (personIndex > -1) {
            people.splice(personIndex, 1, person)
            return;
        }
    } else {
        person.id = getNextId();
    }

    people.push(person);
}

function find(personId) {
    return people.find(person => person.id == personId)
}

function remove(personId) {
    let id = personId;
    if (typeof personId == 'object') {
        if (!person.id) {
            throw Error('invalid person object provided');
        }
        id = person.id
    }
    const personIdx = people.findIndex(i => i.id = id);

    people.splice(personIdx, 1)
}

function clear() {
    people.splice(0, people.length);
}

export default {
    add: add,
    all: all,
    find: find,
    remove: remove,
    people: people,
    clear: clear
}