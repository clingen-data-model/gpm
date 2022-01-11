import {cloneDeep, isEqual} from 'lodash';
import {ref, watch} from 'vue';

export default (props, context) => {
    const workingCopy = ref({});
    watch(() => props.modelValue, function (to) {
        if (!isEqual(to, workingCopy.value)) {
            workingCopy.value = cloneDeep(to);
        }
    }, {immediate: true});

    watch(() => workingCopy, function (to) {
        if (!isEqual(to, props.modelValue)) {
            context.emit('update:modelValue', to.value);
        }
    }, {deep: true});

    return {
        workingCopy,
    }
}
