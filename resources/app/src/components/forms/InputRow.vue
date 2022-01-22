<template>
    <div 
        :class="{'border-l border-red-800 px-2': hasErrors}" 
        class="input-row my-3"
    >
        <div :class="{'sm:flex': !vertical}">
            <div class="flex-none label-container" :class="labelContainerClass" v-show="showLabel">
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
                        :readonly="$attrs.readonly"
                        @change="$emit('change', modelValue)"
                        ref="input"
                        :name="name"
                        :class="inputClass"
                    ></date-input>
                    <textarea 
                        v-if="type == 'large-text'"
                        :value="modelValue" 
                        @input="$emit('update:modelValue', $event.target.value)"
                        :disabled="disabled"
                        :readonly="$attrs.readonly"
                        @change="$emit('change', modelValue)"
                        ref="input"
                        :name="name"
                        :class="inputClass"
                        class="w-full"
                        rows="5"
                        :placeholder="placeholder"
                    ></textarea>
                    <div 
                        v-else-if="type == 'radio-group'"
                        class="radio-group"
                        :class="{'ml-4': vertical}"
                    >
                        <radio-button 
                            v-for="option in options"
                            :key="option.value"
                            :modelValue="modelValue" 
                            @update:modelValue="emitValue" 
                            :label="option.label || sentenceCase(option.value)" 
                            :value="option.value"
                            :disabled="disabled"
                            :readonly="$attrs.readonly"
                        />
                    </div>
                    <select v-else-if="type=='select'"
                        :value="modelValue"
                        @input="$emit('update:modelValue', $event.target.value)"
                        v-bind="$attrs.disabled"
                        :disabled="disabled"
                        :readonly="$attrs.readonly"
                    >
                        <option value="">Select&hellip;</option>
                        <template v-for="option in options" :key="option.value">
                            <slot name="option-label" v-bind="option">
                                <option :value="option.value">
                                    {{option.label || sentenceCase(option.value)}}
                                </option>
                            </slot>
                        </template>
                    </select>
                    <input 
                        v-else
                        :type="type" 
                        :value="modelValue" 
                        @input="$emit('update:modelValue', $event.target.value)"
                        :placeholder="placeholder"
                        :disabled="disabled"
                        :readonly="$attrs.readonly"
                        @change="$emit('change', $event.target.value)"
                        ref="input"
                        :class="inputClass"
                        :name="name"
                    >
                </slot>
                <slot name="after-input"></slot>
                <input-errors class="text-xs" :errors="errors || []"></input-errors>
            </div>
        </div>
    </div>
</template>
<script>

export default {
    props: {
        type: {
            type: String,
            required: false,
            default: 'text'
        },
        modelValue: {
            required: false,
            default: null
        },
        errors: {
            type: Array,
            required: false,
            default: () => []
        },
        options: {
            type: Array,
            required: false
        },
        label: {
            type: String,
            required: false
        },
        // purgeCSS purges "unused" classes
        // this means any otherwise unused width
        // class will be purged and will not apply
        // when we generate the class based on a number
        // labelWidth: {
        //     type: Number,
        //     required: false,
        //     default: 36
        // },
        labelWidthClass: {
            type: String,
            required: false,
            default: 'w-36'
        },
        placeholder: {
            required: false,
            value: null
        },
        disabled: {
            type: Boolean,
            default: false,
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
        },
        vertical: {
            type: Boolean,
            default: false
        },
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
                classes.push(this.labelWidthClass);
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
