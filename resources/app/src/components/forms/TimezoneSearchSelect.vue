<template>
    <div class="flex space-x-1 items-start">
        <div>
            <label for="region-select" class="block text-xs">Region:</label>
            <select id="region-select" v-model="region" class="input-cl">
                <option :value="region" v-for="region in regions" :key="region">
                    {{region}}
                </option>
            </select>
        </div>
        <div>
            <label for="city-search-select" class="block text-xs">City:</label>
            <select id="city-search-select" v-model="city" :disabled="!this.region">
                <option value="null">Select...</option>
                <option  v-for="{name} in regionCities" :key="name" :value="name">{{name}}</option>
            </select>
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
    emits: [
        'update:modelValue'
    ],
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
        regionCities () {
            return this.cities.filter(c => {
                return c.region == this.region;
            })
        }
    },
    watch: {
        city (to) {
            const newValue = this.region+'/'+to.replace(' ', '_');
            if (!this.timezones.indexOf(newValue)) {
                return;
            }
            this.$emit('update:modelValue', newValue);
        },
        region (to) {
            if (to == 'UTC') {
                this.$emit('update:modelValue', to);
            }

            const newValue = to+'/'+this.city;

            if (!this.timezones.indexOf(newValue)) {
                return;
            }
            this.$emit('update:modelValue', newValue);
        },
        modelValue: {
            immediate: true,
            handler (to) {
                if (!to) {
                    return;
                }

                const [region, ...cityParts] = to.split('/');
                const city = cityParts.join('/').replace('_', ' ');
                if (region != this.region) {
                    this.region = region;
                }
                if (city != this.city) {
                    this.city = city;
                }
            }
        }
    },
    methods: {
        search (searchText) {
            if (!searchText || searchText.length < 2) {
                return [];
            }
            const pattern = new RegExp(`.*${searchText}.*`, 'i');
            return this.cities
                .filter(c => {
                    if (this.region) {
                        return c.region == this.region;
                    }
                    return true;
                })
                .filter(c => {
                    return (c.name.match(pattern));
                })
        },
        setRegionsAndCities(timezones) {
            timezones.forEach(tz => {
                const [region, ...city] = tz.split('/');
                this.regions.push(region);
                this.cities.push({region: region, name: city.join('/').replace('_', ' ')});
            })
            this.regions = [...(new Set(this.regions))];
        }
    },
    mounted () {
        api.get('/api/people/lookups/timezones')
            .then(response => {
                this.timezones = response.data.data.filter(tz => tz != 'UTC')
                this.setRegionsAndCities(this.timezones);
            })
    }
}
</script>