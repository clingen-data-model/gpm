<template>
    <label class="mb-2 flex items-start cursor-pointer">
        <div class="mt-1">
            <slot name="input">
                <input 
                    type="checkbox" 
                    v-model="val" 
                    :value="value"
                    :id="checkboxId"
                    v-bind="$attrs"
                    @change="propagateChange"
                >
            </slot>
        </div>
        <div>
            <slot>
                {{label}}
            </slot>
        </div>
    </label>
</template>
<script>
export default {
    name: 'Checkbox',
    props: {
        modelValue: {
            required: false,
            default: false
        },
        label: {
            required: false
        },
        value: {
            required: false,
            default: () => true
        }
    },
    emits: [
        'update:modelValue',
        'change'
    ],
    computed: {
        checkboxId () {
            return `checkbox-${this.uid}`;
        },
        val: {
            get () {
                return this.modelValue
            },
            set (val) {
                this.$emit('update:modelValue', val)
            }
        }
    },
    methods: {
        propagateChange (evt) {
            this.$emit('change', evt);
        } 
    }
}
</script>