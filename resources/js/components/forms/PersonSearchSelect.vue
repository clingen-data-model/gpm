<script>
import {api} from '@/http'
import SearchSelect from '@/components/forms/SearchSelect.vue'

export default {
    name: 'PersonSearchSelect',
    components: {
        SearchSelect,
    },
    props: {
        modelValue: {
            required: true,
        },
        allowAdd: {
            type: Boolean,
            default: true
        },
        multiple: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            options: [],
            showAddForm: false,
            formName: null,
        }
    },
    computed: {
        selectedPerson: {
            get () {
                return this.modelValue
            },
            set (value) {
                if (value) {
                    this.$emit('update:modelValue', value);
                    return;
                }

                this.$emit('update:modelValue', null);
            }
        }
    },
    methods: {
        async search (searchText) {
            if (!searchText || searchText.length < 2) {
                return [];
            }
            const url = `/api/people?where[filterString]=${searchText}`;
            this.options = await api.get(url)
                    .then(response => {
                        return response.data.data;
                    })
            return this.options;
        },
    },
}
</script>
<template>
    <div>
        <SearchSelect
            v-model="selectedPerson"
            :multiple="multiple"
            :disabled="disabled"
            :search-function="search"
            style="z-index: 2"
            placeholder="Person name or email"
            @update:modelValue="searchText = null"
        >
            <template v-slot:selection-label="{selection}">
                <div>
                    {{selection.first_name}} {{selection.last_name}} &lt;{{selection.email}}&gt;
                </div>
            </template>
            <template v-slot:option="{option}">
                <div v-if="typeof option == 'object'">
                    {{option.name}} &lt;{{option.email}}&gt;
                </div>
                <div v-else>
                    {{option}}
                </div>
            </template>
        </SearchSelect>
    </div>
</template>
