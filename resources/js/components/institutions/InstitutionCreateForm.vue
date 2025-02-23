<template>
    <div>
        <data-form :fields="filteredFields" :errors="errors" v-model="inst" />
        <button-row submit-text="Save" @submitted="save" @cancel="cancel" />
    </div>
</template>
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
    data() {
        return {
            inst: {
            },
            errors: {}
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
    computed: {
        filteredFields () {
            return this.fields.filter(f => f.name !== 'reportable');
        }
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
    },
    setup() {
        return {
            fields,
            getCountries,
            countries,
            createInstitution
        }
    },
    mounted () {
        this.getCountries();
    }
}
</script>
