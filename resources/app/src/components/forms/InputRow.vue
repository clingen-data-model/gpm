<template>
    <div :class="{'border-l border-red-800 px-2': hasErrors}" class="input-row my-3">
        <div :class="{'sm:flex': !vertical}">
            <div class="flex-none" :class="labelContainerClass" v-show="showLabel">
                <slot name="label" v-if="hasLabel">
                    <label :class="resolvedLabelClass">{{label}}{{colon}}</label>
                </slot>
            </div>
            <div class="flex-grow">
                <slot>
                    <date-input 
                        v-if="type == 'date'"
                        :modelValue="modelValue" 
                        @update:modelValue="emitValue" 
                        :disabled="disabled"
                        @change="$emit('change', modelValue)"
                        ref="input"
                        :name="name"
                        :class="inputClass"
                    ></date-input>
                    <input 
                        v-else
                        :type="type" 
                        :value="modelValue" 
                        @input="$emit('update:modelValue', $event.target.value)"
                        :placeholder="placeholder"
                        :disabled="disabled"
                        @change="$emit('change', $event.target.value)"
                        ref="input"
                        :class="inputClass"
                        :name="name"
                    >
                </slot>
                <slot name="after-input"></slot>
                <input-errors class="text-xs" :errors="errors"></input-errors>
            </div>
        </div>
    </div>
</template>
<script>

export default {
    props: {
        vertical: {
            type: Boolean,
            default: false
        },
        errors: {
            type: Array,
            required: false,
            default: () => []
        },
        label: {
            type: String,
            required: false
        },
        labelWidth: {
            type: Number,
            required: false,
            default: 36
        },
        type: {
            type: String,
            required: false,
            default: 'text'
        },
        modelValue: {
            required: false,
            default: null
        },
        placeholder: {
            required: false,
            value: null
        },
        disabled: {
            required: false,
            default: false,
            type: Boolean
        },
        name: {
            required: false,
            default: null,
            type: String
        },
        inputClass: {
            required: false,
            default: null,
            type: String
        },
        hideLabel: {
            required: false,
            default: false,
            type: Boolean
        },
        labelClass: {
            required: false,
            default: null,
            type: String
        }
    },
    emits: [
        'update:modelValue',
        'change'
    ],
    computed: {
        colon () {
            if (this.label && [':',';','.','?', '!'].includes(this.label.substr(-1))) {
                return '';
            }
            return ':';    
        },
        hasErrors () {
            return this.errors.length > 0;
        },
        labelContainerClass () {
            const classes = [];
            if (this.vertical) {
                classes.push('my-1');
            } else {
                classes.push(`w-${this.labelWidth}`);
            }

            return classes.join(' ');
        },
        showLabel () {
            return !this.hideLabel;
        },
        hasLabel () {
            return this.label || this.$slots.label
        },
        resolvedLabelClass () {
            const classes = [];
            if (this.hasErrors) {
               classes.push('text-red-800');
            }
            if (this.labelClass) {
                classes.push(this.labelClass);
            }

            return classes.join(' ');
        },
    },
    methods: {
        emitValue(evt) {
            this.$emit('update:modelValue', evt)
        },
        focus() {
            this.$refs.input.focus();
        }

    }
}
</script>