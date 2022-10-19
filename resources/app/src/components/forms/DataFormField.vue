<script setup>
    import {defineEmits, computed, h} from 'vue'
    import {setupMirror, mirrorProps, mirrorEmits} from '@/composables/setup_working_mirror'
    import { titleCase } from '@/string_utils.js'
    import {set, get} from 'lodash'
    import InputRowVue from './InputRow.vue';
    import DictionaryRowVue from '../DictionaryRow.vue';

    const props = defineProps({
        ...mirrorProps,
        field: {
            type: Object,
            required: true,
        },
        errors: {
            type: Object,
            required: true
        },
    });

    const {workingCopy} = setupMirror(props, {emit});

    const emit = defineEmits(mirrorEmits);

    const fieldValue = computed({
        get () {
            if (props.field.name === '*') {
                return workingCopy.value
            }
            const val = get(workingCopy.value, props.field.name);
            return val;
        },
        set (value) {
            if (props.field.name === '*') {
                workingCopy.value = value
                return;
            }
            if (get(workingCopy.value, props.field.name) !== value) {
                set(workingCopy.value, props.field.name, value);
            }
        }
    })

    const getFieldLabel = (field) => {
        if (typeof field.label === 'undefined') {
            return titleCase(field.name);
        }
        return  field.label
    }

    const resolveOptions = (field) => {
        if (Array.isArray(field.options)) {
            return field.options
        }
        if (typeof field.options == 'function') {
            return field.options(workingCopy);
        }
        return [];
    }

    const evalShow = (field) => {
        if (field.show) {
            return field.show(workingCopy);
        }
        return true;
    }

    const renderChildren = () => {
        if (props.field.type == 'dictionary-row') {
            return [h(DictionaryRowVue, {innerHTML: fieldValue.value, label: getFieldLabel(props.field), class: props.field.class})]
        }
        if (props.field.type == 'component') {
            return [renderComponent()]
        }


        return [renderInputRow()];
    }

    const renderInputRow = (defaultSlotFunction = null) => {
        const options = {
            label: getFieldLabel(props.field),
            modelValue: fieldValue.value ,
            'onUpdate:modelValue': (value) => { fieldValue.value = value },
            type: props.field.type || 'text',
            placeholder: props.field.placeholder || null,
            errors: props.errors[props.field.name],
            options: resolveOptions(props.field),
            vertical: props.field.vertical,
            class: props.field.class,
            required: props.field.required,
        }
        return h( InputRowVue, options, defaultSlotFunction)
    }

    const renderComponent = () => {
        const renderComponent = () => {
            const options = {
                ...props.field.component.options,
                modelValue: fieldValue.value,
                'onUpdate:modelValue': (value) => { fieldValue.value = value },
                errors: props.errors
            }

            return h(props.field.component.component, options,  props.field.component.slots)
        }
        return renderInputRow(renderComponent)
    }

    const render = () => {
        const children = renderChildren()
        const container = h('div', {class: ''}, evalShow(props.field) ? children : []);

        return container
    }



</script>

<template>
    <render />
</template>
