<script setup>
    import {ref, onBeforeUpdate, h} from 'vue'
    import DataFormField from '@/components/forms/DataFormField.vue'
    import mirror from '@/composables/setup_working_mirror'
    import {v4 as uuid4} from 'uuid'

    const props = defineProps({
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
            wrapperClass: {
                type: String || null,
                default: null
            }
        });

    const emits = defineEmits([...mirror.emits])
    const formId = uuid4()
    const fieldRefs = ref([])

    const setFieldRef = (el) => {
        fieldRefs.value.push(el)
    }

    // const focus = () => {
    //     if (fieldRefs.value.length > 0) {
    //         fieldRefs.value[0].focus();
    //     }
    // }

    const {workingCopy} = mirror.setup(props, {emit: emits});

    onBeforeUpdate(() => {
        fieldRefs.value = [];
    })

    const renderElement = ({field}) => {
        if (field.type === 'raw-component') {
            return h(
                field.component.component, 
                field.component.options, 
                field.component.slots
            );
        }
        return h(
            DataFormField, 
            {
                field, 
                modelValue: workingCopy.value, 
                'onUpdate:modelValue': (value) => {
                    emits('update:modelValue', value)
                },
                ref: setFieldRef(),
                class: 'flex-grow',
                errors: props.errors,
            },
         )
    }

    const renderExtra = ({field}) => {
            let extraSlot = null;
            if (field.extraSlot) {
                extraSlot = h(
                    field.extraSlot, 
                    {
                        field, 
                        modelValue: workingCopy.value, 
                        'onUpdate:modelValue': (value) => {
                            emits('update:modelValue', value)
                        }
                    }
                )
            }
        return extraSlot;
    }
    
</script>
<template>
    
    <div class="data-form" :id="formId">
        <div v-for="field in fields" :key="field.name">
            <div :class="wrapperClass">
                <renderElement :field="field" :modelValue="workingCopy" />
                <renderExtra :field="field" :modelValue="workingCopy" />
            </div>
        </div>
    </div>
</template>