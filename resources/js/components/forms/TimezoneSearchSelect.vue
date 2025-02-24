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
            <select id="city-search-select" v-model="city" :disabled="!region">
                <option value="null">Select...</option>
                <option  v-for="{name} in regionCities" :key="name" :value="name">{{name}}</option>
            </select>
            &nbsp;
            <popover arrow trigger="hover">
                <template v-slot:content>
                    <div class="w-80">
                        <p>The best way to get your timezone right is to tell us which city has the same “timezone” you do.  For example, if you’re located in Atlanta, Georgia, USA you should choose, North America/New York as your “Timezone”.</p>
                        <p>See the <faq-link hash="#heading=h.9kox4w1eoeul" /> for more details.</p>
                    </div>
                </template>
                <icon-question class="text-gray-500 cursor-pointer" />
            </popover>
        </div>
    </div>
</template>
<script>
import api from '@/http/api'

export default {
    name: 'DiseaseSearchSelect',
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
                return c.region === this.region;
            })
        }
    },
    watch: {
        city () {
            this.emitUpdate()
        },
        region () {
            this.emitUpdate()
        },
        modelValue: {
            immediate: true,
            handler (to) {
                let region = null;
                let cityParts = [];
                let city = null;
                if (!to) {
                    return;
                    // const currentTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    // if (currentTz) {
                    //     [region, ...cityParts] = currentTz.split('/').map(i => i.replace('_', ' '));
                    //     city = cityParts.join('/').replace('_', ' ');
                    // }
                } else {
                    [region, ...cityParts] = to.split('/');
                    city = cityParts.join('/').replace('_', ' ');
                }

                let updated = false;
                if (region !== this.region) {
                    this.region = region;
                    updated = true
                }
                if (city !== this.city) {
                    this.city = city;
                    updated = true
                }

                if (updated) {
                    this.emitUpdate();
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
                        return c.region === this.region;
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
                this.cities.push({region, name: city.join('/').replace('_', ' ')});
            })
            this.regions = [...(new Set(this.regions))];
        },
        emitUpdate () {
            if (this.region === 'UTC') {
                this.$emit('update:modelValue', this.region);
            }

            if (!this.city || !this.region) {
                return;
            }

            const newValue = `${this.region}/${this.city.replace(' ', '_')}`;
            if (!this.timezones.indexOf(newValue)) {
                return;
            }
            this.$emit('update:modelValue', newValue);
        }
    },
    mounted () {
        api.get('/api/people/lookups/timezones')
            .then(response => {
                this.timezones = response.data.data.filter(tz => tz !== 'UTC')
                this.setRegionsAndCities(this.timezones);
            })
    }
}
</script>