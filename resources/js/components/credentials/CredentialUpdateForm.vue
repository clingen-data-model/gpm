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
    data() {
        return {
            errors: {}
        }
    },
    methods: {
        async save () {
            try {
                this.initErrors();
                // eslint-disable-next-line no-console
                console.log('save!');
                const newCred = await this.$store.dispatch('credentials/update', this.workingCopy)
                                    .then(rsp => rsp.data);

                // eslint-disable-next-line no-console
                console.log(newCred)
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
    setup(props, context) {
        const {workingCopy} = setupMirror(props, context)

        return {
            workingCopy
        }
    },
}
</script>
<template>
    <div>
        <input-row label="Name" v-model="workingCopy.name" :errors="errors.name" />
        <button-row submit-text="Save" @submitted="save" @cancel="cancel" />
    </div>
</template>
