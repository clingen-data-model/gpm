<template>
    <div :class="{'border-l border-red-800 px-2': hasErrors}" class="input-row">
        <div class="my-3" :class="{'sm:flex': !vertical}">
            <div class="flex-none" :class="{'w-36': !vertical, 'my-1': vertical}">
                <slot name="label" v-if="label">
                    <label :class="{'text-red-800': hasErrors}">{{label}}{{colon}}</label>
                </slot>
            </div>
            <div class="flex-grow">
                <slot>
                    <date-input 
                        v-if="type == 'date'"
                        :modelValue="modelValue" 
                        @update:modelValue="emitValue" 
                        :disabled="disabled"
                        @change="$emit('change')"
                        ref="input"
                        :name="name"
                    ></date-input>
                    <input 
                        v-else
                        :type="type" 
                        :value="modelValue" 
                        @input="$emit('update:modelValue', $event.target.value)"
                        :placeholder="placeholder"
                        :disabled="disabled"
                        @change="$emit('change')"
                        ref="input"
                        class="w-"
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
import InputErrors from './InputErrors'

export default {
    components: {
        InputErrors
    },
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