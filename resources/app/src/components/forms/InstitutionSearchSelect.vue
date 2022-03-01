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
            <template v-slot:additionalOption>
                <button class="font-bold link cursor-pointer" @click="initAddNew">Add your institution</button>
            </template>
        </search-select>
        <teleport to='body'>
            <modal-dialog title="Add an institution" v-model="showAddForm">
                <institution-form 
                    :name="formName"
                    @saved="useNewInstitution" 
                    @canceled="cancelNewInstitution" 
                />
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import api from '@/http/api'
import SearchSelect from '@/components/forms/SearchSelect'
import InstitutionForm from '@/components/institutions/InstitutionCreateForm'

export default {
    name: 'InstitutionSearchSelect',
    components: {
        SearchSelect,
        InstitutionForm
    },
    props: {
        modelValue: {
            required: true,
        },
    },
    data() {
        return {
            institutions: [],
            showAddForm: false,
            formName: null,
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
            if (!searchText || searchText.length < 1) {
                return [];
            }
            this.formName = searchText;
            const pattern = new RegExp(`.*${searchText}.*`, 'i');
            return this.institutions.filter(i => {
                return (i.name &&  i.name.match(pattern))
                    || (i.abbreviation && i.abbreviation.match(pattern))
                    || (i.url && i.url.match(pattern))
            })
        },
        initAddNew () {
            this.showAddForm = true;
        },
        useNewInstitution (newInst) {
            this.institutions.push(newInst);
            this.selectedInstitution = newInst;
            this.showAddForm = false;
        },
        cancelNewInstitution () {
            this.showAddForm = false;
        },
        getInstitutions () {
            api.get('/api/people/institutions')
                .then(response => {
                    this.institutions = response.data
                })
        }
    },
    mounted () {
        this.getInstitutions()
    }
}
</script>