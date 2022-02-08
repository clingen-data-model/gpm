<template>
    <div>
        <search-select 
            v-model="selectedInstitution" 
            :search-function="search"
            style="z-index: 2"
            placeholder="Institution name or abbreviation"
            @update:modelValue="searchText = null"
        >
            <template v-slot:selection-label="{selection}">
                <div>
                    {{selection.name}} <span v-if="selection.abbreviation">({{selection.abbreviation}})</span>
                </div>
            </template>
            <template v-slot:option="{option}">
                <div v-if="typeof option == 'object'">
                    {{option.name}} <span v-if="option.abbreviation">({{option.abbreviation}})</span>
                </div>
                <div v-else>
                    {{option}}
                </div>
            </template>
        </search-select>
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
            institutions: []
        }
    },
    computed: {
        selectedInstitutionId: {
            get () {
                return this.modelValue;
            },
            set (value) {
                this.$emit('update:modelValue', value);
            }
        },
        selectedInstitution: {
            get () {
                return this.institutions.find(i => i.id == this.modelValue)
            },
            set (value) {
                if (value) {
                    this.selectedInstitutionId = value.id;
                    return;
                }

                this.selectedInstitutionId = null;

            }
        }
    },
    methods: {
        search (searchText) {
            if (!searchText || searchText.length < 2) {
                return [];
            }
            const pattern = new RegExp(`.*${searchText}.*`, 'i');
            return this.institutions.filter(i => {
                return (i.name &&  i.name.match(pattern))
                    || (i.abbreviation && i.abbreviation.match(pattern))
                    || (i.url && i.url.match(pattern))
            })
        }
    },
    mounted () {
        api.get('/api/people/institutions')
            .then(response => {
                this.institutions = response.data
            })
    }
}
</script>