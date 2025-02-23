import {cloneDeep, isEqual} from 'lodash-es';
import {ref, watch} from 'vue';

export const mirrorProps = {
    modelValue: {
        type: Object,
    }
};

export const mirrorEmits = [
    'update:modelValue'
];


export const setupMirror = (props, context) => {
    const workingCopy = ref({});
    watch(() => props.modelValue, (to) => {
        if (typeof props.modelValue.clone == 'function') {
            const clone = props.modelValue.clone();
            workingCopy.value = clone;
        }
        if (!isEqual(to, workingCopy.value)) {
            workingCopy.value = cloneDeep(to);
        }
    }, {immediate: true, deep: true});

    watch(() => workingCopy, (to) => {
        if (!isEqual(to.value, props.modelValue)) {
            context.emit('update:modelValue', to.value);
        }
    }, {deep: true});

    return {
        workingCopy,
    }
}

export default {
    props: mirrorProps,
    emits: mirrorEmits,
    setup: setupMirror
};
