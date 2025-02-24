<script>
import SearchSelect from '@/components/forms/SearchSelect.vue'
import InstitutionForm from '@/components/institutions/InstitutionCreateForm.vue'
import {api, queryStringFromParams} from '@/http'

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
        allowAdd: {
            type: Boolean,
            default: true
        }
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
                return this.institutions.find(i => i.id === this.modelValue)
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
    mounted () {
        this.getInstitutions()
    },
    methods: {
        search (searchText) {
            // if (!searchText || searchText.length < 1) {
            //     return this.institutions.slice(0, 20);
            // }
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
            const query = {
                sort: {field: 'name', dir: 'asc'},
                select: ['name', 'abbreviation', 'id', 'url']
            }
            api.get(`/api/people/institutions${queryStringFromParams(query)}`)
                .then(response => {
                    this.institutions = response.data
                })
        }
    }
}
</script>
<template>
    <div>
        <SearchSelect
            v-model="selectedInstitution"
            :search-function="search"
            style="z-index: 2"
            placeholder="Institution name or abbreviation"
            keyOptionsBy="id"
            showOptionsOnFocus
            :options="institutions"
            showOptionsWhenEmpty
            @update:modelValue="searchText = null"
        >
            <template #selection-label="{selection}">
                <div>
                    {{ selection.name }} <span v-if="selection.abbreviation">({{ selection.abbreviation }})</span>
                </div>
            </template>
            <template #option="{option}">
                <div v-if="typeof option == 'object'">
                    {{ option.name }} <span v-if="option.abbreviation">({{ option.abbreviation }})</span>
                </div>
                <div v-else>
                    {{ option }}
                </div>
            </template>
            <template v-if="allowAdd" #fixedBottomOption>
                <button class="font-bold link cursor-pointer" @click="initAddNew">Add your institution</button>
            </template>
        </SearchSelect>
        <teleport to='body'>
            <modal-dialog v-model="showAddForm" title="Add an institution">
                <InstitutionForm
                    :name="formName"
                    @saved="useNewInstitution"
                    @canceled="cancelNewInstitution"
                />
            </modal-dialog>
        </teleport>
    </div>
</template>
