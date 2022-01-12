<script>
// import {ref, watch} from 'vue';
import InputRow from '@/components/forms/InputRow'
// import SearchSelect from '@/components/forms/SearchSelect'
import { titleCase } from '@/utils'

export default {
    name: 'DataForm',
    components: {
        InputRow,
        // SearchSelect,
    },
    props: {
        fields: {
            type: Array,
            required: true,
        },
        modelValue: {
            // type: Object,
            required: true
        },
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    emits: [
        'update:modelValue'
    ],
    computed: {
        dataClone: {
            deep: true,
            immediate: true,
            get () {
                return this.modelValue;
            },
            set (value) {
                this.$emit(value)
            }
        }
    },
    methods: {
        getFieldLabel (field) {
            return (field.label ? titleCase(field.label) : titleCase(field.name))
        },
        selectOrTextarea (field) {
            return ['textarea', 'select'].includes(field.type);
        },
        searchSelect (field) {
            return field.type == 'search-select'
        },
        emitChange() {
            this.$emit('update:modelValue', this.dataClone);
        },
        evalShow(field) {
            if (field.show) {
                return field.show(this.dataClone);
            }
            return true;
        }
    },
}
</script>
<template>
    <div class="data-form">
        <div v-for="field in fields" :key="field.name">
            <div v-if="evalShow(field)">
                <input-row 
                    v-if="selectOrTextarea(field) || searchSelect(field) || field.type == 'component'" 
                    :label="getFieldLabel(field)"
                    :class="field.class"
                    :errors="errors[field.name]"
                >
                    <select v-if="field.type == 'select'"
                        v-model="dataClone[field.name]" 
                    >
                        <option :value="null">Select...</option>
                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{option.label}}</option>
                    </select>
                    <textarea v-else-if="field.type == 'textarea'" 
                        v-model="dataClone[field.name]" 
                        class="w-full"
                        rows="5"
                    ></textarea>
                    <component v-else-if="field.type == 'component'" :is="field.component" v-model="dataClone[field.name]"></component>
                </input-row>
                <input-row v-else 
                    :label="getFieldLabel(field)" 
                    v-model="dataClone[field.name]" 
                    :type="field.type || 'text'"
                    :placeholder="field.placeholder || null"
                    :errors="errors[field.name]"
                ></input-row>
            </div>
        </div>
    </div>
</template>
