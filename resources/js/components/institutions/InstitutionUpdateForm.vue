<script>
import {setupMirror} from '@/composables/setup_working_mirror'
import {fields, getCountries, countries, updateInstitution} from '@/forms/institution_form'
import {isValidationError} from '@/http'

export default {
    name: 'InstitutionCreateForm',
    props: {
        modelValue: {
            type: Object,
            required: true
        }
    },
    emits: [
        'update:modelValue',
        'saved',
        'canceled'
    ],
    setup(props, context) {
        const {workingCopy} = setupMirror(props, context)
        
        return {
            fields,
            getCountries,
            countries,
            updateInstitution,
            workingCopy
        }
    },
    data() {
        return {
            errors: {}
        }
    },
    mounted () {
        this.getCountries();
    },
    methods: {
        async save () {
            try {
                this.initErrors();
                const newInst = await this.updateInstitution(this.workingCopy);
                this.$emit('update:modelValue', newInst);
                this.$emit('saved', newInst)
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
        initErrors () {
            this.errors = {};
        }
    }
}
</script>
<template>
    <div>
        <data-form v-model="workingCopy" :fields="fields" :errors="errors" />
        <button-row submit-text="Save" @submitted="save" @cancel="cancel" />
    </div>
</template>