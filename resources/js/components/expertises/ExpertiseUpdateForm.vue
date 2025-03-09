<script>
import {setupMirror} from '@/composables/setup_working_mirror'
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
            workingCopy
        }
    },
    data() {
        return {
            errors: {}
        }
    },
    methods: {
        async save () {
            try {
                this.initErrors();
                const newCred = await this.$store.dispatch('expertises/update', this.workingCopy)
                                    .then(rsp => rsp.data);

                this.$emit('update:modelValue', newCred);
                this.$emit('saved', newCred)
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
    },
}
</script>
<template>
  <div>
    <input-row v-model="workingCopy.name" label="Name" :errors="errors.name" />
    <button-row submit-text="Save" @submitted="save" @cancel="cancel" />
  </div>
</template>
