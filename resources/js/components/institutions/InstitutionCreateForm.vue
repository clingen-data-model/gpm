<script>
import {countries, createInstitution, fields, getCountries} from '@/forms/institution_form'
import {isValidationError} from '@/http'

export default {
    name: 'InstitutionCreateForm',
    props: {
        name: {
            type: String||null,
            default: null
        }
    },
    emits: [
        'saved',
        'canceled'
    ],
    setup() {
        return {
            fields,
            getCountries,
            countries,
            createInstitution
        }
    },
    data() {
        return {
            inst: {
            },
            errors: {}
        }
    },
    computed: {
        filteredFields () {
            return this.fields.filter(f => f.name !== 'reportable');
        }
    },
    watch: {
        name: {
            immediate: true,
            handler (to) {
                this.inst.name = to;
            }
        }
    },
    mounted () {
        this.getCountries();
    },
    methods: {
        async save () {
            try {
                const newInst = await this.createInstitution(this.inst);
                this.$emit('saved', newInst)
                this.initInstitution()
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors
                }
            }
        },
        cancel () {
            this.$emit('canceled')
            this.initInstitution()
        },
        initInstitution () {
            this.inst = {
                name: this.name
            };
        },
        initErrors () {
            this.errors = {};
        }
    }
}
</script>
<template>
    <div>
        <data-form v-model="inst" :fields="filteredFields" :errors="errors" />
        <button-row submit-text="Save" @submitted="save" @cancel="cancel" />
    </div>
</template>
