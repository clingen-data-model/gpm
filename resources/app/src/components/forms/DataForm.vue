<script>
import DataFormField from '@/components/forms/DataFormField.vue'
import mirror from '@/composables/setup_working_mirror'
import {v4 as uuid4} from 'uuid'

export default {
    name: 'DataForm',
    components: {
        DataFormField
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        },
        fields: {
            type: Array,
            required: true,
        },
    },
    emits: [
        ...mirror.emits
    ],
    data () {
        return {
            formId: uuid4(),
            fieldRefs: []
        }
    },
    methods: {
        setFieldRef(el) {
            this.fieldRefs.push(el)
        },
        focus () {
            if (this.fieldRefs.length > 0) {
                this.fieldRefs[0].focus();
            }
        }
    },
    beforeUpdate () {
        this.fieldRefs = [];
    },
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>
<template>
    <div class="data-form">
        <!-- <pre>{{this.farts}}</pre> -->
        <div v-for="field in fields" :key="field.name">
            
            <data-form-field
                v-model="workingCopy"
                :field="field"
                :errors="errors"
                :ref="setFieldRef"
            />

        </div>
    </div>
</template>
