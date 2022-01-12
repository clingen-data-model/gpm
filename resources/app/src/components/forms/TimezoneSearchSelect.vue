<template>
    <div class="flex space-x-1 items-start">
        <!-- <div>
            <label for="region-select" class="block text-xs">Region:</label>
            <select id="region-select" v-model="region" class="input-cl">
                <option :value="region" v-for="region in regions" :key="region">
                    {{region}}
                </option>
            </select>
        </div> -->
        <div>
            <!-- <label for="city-search-select" class="block text-xs">City:</label> -->
            <search-select 
                id="city-search-select"
                v-model="selectedTimezone" 
                :search-function="search"
                style="z-index: 2"
                placeholder="Name of Closest City"
                @update:modelValue="searchText = null"
            >
                <template v-slot:selection-label="{selection}">
                    <div>
                        {{selection}}
                    </div>
                </template>
                <template v-slot:option="{option}">
                    <div>
                        {{option}}
                    </div>
                </template>
            </search-select>
        </div>
    </div>
</template>
<script>
import api from '@/http/api'
import SearchSelect from '@/components/forms/SearchSelect'

export default {
    name: 'DiseaseSearchSelect',
    components: {
        SearchSelect,
    },
    props: {
        modelValue: {
            required: true,
        },
    },
    data() {
        return {
            timezones: [],
            regions: [],
            cities: [],
            region: null,
            city: null
        }
    },
    computed: {
        selectedTimezone: {
            get () {
                return this.modelValue;
            },
            set (value) {
                this.$emit('update:modelValue', value);
            }
        }
    },
    methods: {
        search (searchText) {
            if (!searchText || searchText.length < 2) {
                return [];
            }
            const pattern = new RegExp(`.*${searchText}.*`, 'i');
            return this.timezones.filter(tz => {
                return (tz.match(pattern));
            })
        },
        setRegionsAndCities(timezones) {
            timezones.forEach(tz => {
                const [region, ...city] = tz.split('/', 1);
                this.regions.push(region);
                this.cities.push(city.join('/'));
            })
            this.regions = [...(new Set(this.regions))];
        }
    },
    mounted () {
        api.get('/api/people/lookups/timezones')
            .then(response => {
                this.timezones = response.data.data
                this.setRegionsAndCities(response.data.data);
                this.setCities(response.data.data)
            })
    }
}
</script>