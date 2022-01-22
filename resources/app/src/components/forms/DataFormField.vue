<template>
    <div v-if="evalShow(field)">
        <dictionary-row v-if="field.type == 'dictionary-row'"
            :label="getFieldLabel(field)"
            :class="field.class"
        >
            {{fieldValue}}
        </dictionary-row>
        
        <input-row 
            v-else-if="field.type == 'component'" 
            :label="getFieldLabel(field)"
            :class="field.class"
            :errors="errors[field.name]"
            :vertical="field.vertical"
        >
            <component :is="field.component" v-model="fieldValue"></component>
        </input-row>

        <input-row v-else 
            :label="getFieldLabel(field)" 
            v-model="fieldValue" 
            :type="field.type || 'text'"
            :placeholder="field.placeholder || null"
            :errors="errors[field.name]"
            :options="resolveOptions(field)"
            :vertical="field.vertical"
            :class="field.class"
        ></input-row>
    </div>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'
import { titleCase } from '@/utils'
import {set, get} from 'lodash'

export default {
    name: 'DataFormField',
    props: {
        ...mirror.props,
        field: {
            type: Object,
            required: true,
        },
        errors: {
            type: Object,
            required: true
        },
    },
    computed: {
        fieldValue: {
            get () {
                return get(this.workingCopy, this.field.name);
            },
            set (value) {
                if (get(this.workingCopy, this.field.name) !== value) {
                    set(this.workingCopy, this.field.name, value);
                }
            }
        }
    },
    methods: {
        getFieldLabel (field) {
            return (field.label ? field.label : titleCase(field.name))
        },
        resolveOptions (field) {
            if (Array.isArray(field.options)) {
                return field.options
            }
            if (typeof field.options == 'function') {
                return field.options(this.workingCopy);
            }
            return [];
        },
        evalShow(field) {
            if (field.show) {
                return field.show(this.workingCopy);
            }
            return true;
        },
    },
    emits: [
        ...mirror.emits
    ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>