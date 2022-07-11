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
            <component :is="field.component" v-model="fieldValue" :errors="errors"/>
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
        >
            <template v-slot:after-input>
                <div  v-if="field.notes" class="note">{{field.notes}}</div>
            </template>
        </input-row>
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
                if (this.field.name === '*') {
                    return this.workingCopy;
                }
                return get(this.workingCopy, this.field.name);
            },
            set (value) {
                if (this.field.name === '*') {
                    this.workingCopy = value
                    return;
                }
                if (get(this.workingCopy, this.field.name) !== value) {
                    set(this.workingCopy, this.field.name, value);
                }
            }
        }
    },
    methods: {
        getFieldLabel (field) {
            if (typeof field.label === 'undefined') {
                return titleCase(field.name);
            }
            return field.label
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