<template>
    <div>
    <div>
        <input type="text" v-model="testDataFilterTerm">
        <data-table :data="testDataList" :fields="testDataFields" v-model:sort="testDataSort" :filterTerm="testDataFilterTerm" :page-size="4" paginated></data-table>

        <hr>
        <input type="text" v-model="testProviderFilterTerm">
        <data-table 
            :data="testDataProvider" 
            :fields="testProviderFields" v-model:sort="testProviderSort" 
            :filterTerm="testDataFilterTerm" :page-size="5" paginated
            ref="testProviderTable"
        />
    </div>
</template>
<script>
import {sortBy} from 'lodash-es'

export default {
    name: 'ComponentName',
    props: {
        
    },
    data() {
        return {
            testDataList: [
                {id: 1, name: 'beans', species: 'chinchilla'},
                {id: 2, name: 'bird', species: 'cat'},
                {id: 3, name: 'nini', species: 'cat'},
                {id: 4, name: 'early', species: 'dog'},
                {id: 5, name: 'milton', species: 'dog'},
                {id: 6, name: 'ozzy', species: 'dog'},
                {id: 7, name: 'charlie', species: 'dog'},
                {id: 8, name: 'muffin', species: 'dog'},
                {id: 9, name: 'beauregard', species: 'dog'},
                {id: 10, name: 'ranger', species: 'dog'},
                {id: 11, name: 'rummy', species: 'dog'},
            ],
            testDataFields: [
                {name: 'id', sortable: true, type: Number},
                {name: 'name', sortable: true, type: String},
                {name: 'species', sortable: true, type: String},
            ],
            testDataSort: {field: 'name', desc: false},
            testDataFilterTerm: null,

            testProviderFields: [
                {name: 'artist', sortable: true, type: String}, 
                {name: 'album', sortable: true, type: String}
            ],
            testProviderSort: {field: 'artist', desc: false},
            testProviderFilterTerm: null,
            
        }
    },
    computed: {

    },
    methods: {
        async testDataProvider (currentPage, pageSize, sort, setTotalItems) {
            console.log({currentPage, pageSize, sort});

            const allItems = [
                {'artist': 'the cure', 'album': '17 seconds'},
                {'artist': 'the smiths', 'album': 'Meat is Murder'},
                {'artist': 'the clash', 'album': 'London Calling'},
                {'artist': 'Orbital', 'album': 'Insides'},
                {'artist': 'portishead', 'album': 'portishead'},
                {'artist': 'tricky', 'album': 'maxinque'},
                {'artist': 'Miles Davis', 'album': 'Kind of Blue'},
                {'artist': 'John Coltrane', 'album': 'Love Supreme'},
                {'artist': 'Omni Trio', 'album': 'Renegade Snares'},
                {'artist': 'Charles Mingus', 'album': 'Tiajuana Moods'},
                {'artist': 'Soul Coughing', 'album': 'Ruby Vroom'},
            ];

            const rx = new RegExp(`${this.testProviderFilterTerm}`);
            const filteredItems = this.testProviderFilterTerm 
                                    ? allItems.filter(i => i.artist.match(rx) || i.album.match(rx)) 
                                    : allItems;

            let sortedItems = sortBy(filteredItems, sort.field.name);
            if (sort.desc) {
                sortedItems = sortedItems.reverse();
            }

            const startIndex = (currentPage-1) * pageSize;
            const endIndex = startIndex + pageSize - 1;
            setTotalItems(sortedItems.length);

            return sortedItems.slice(startIndex, endIndex);
        },

    }
}
</script>