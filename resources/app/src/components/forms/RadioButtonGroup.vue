<script setup>
import {sentenceCase} from '../../utils';

    const props = defineProps({
        modelValue: {
            required: true
        },
        options: {
            type: Array,
            default: () => []
        },
        labelAttribute: {
            type: String,
            required: false
        },
        vertical: {
            type: Boolean,
            default: false
        },
        size: {
            type: String,
            default: 'md'
        }
    });
    const emits = defineEmits(['update:modelValue', 'change']);

    const selectItem = (item) => {
        emits('update:modelValue', item)
        emits('change', item)
    }

    const isSelected = (opt) => JSON.stringify(opt) == JSON.stringify(props.modelValue);

    const resolveLabel = (option) => {
        if (typeof option == 'object') {
            return props.labelAttribute ? option[props.labelAttribute] : option;
        }

        if (typeof option == 'string') {
            return sentenceCase(option)
        }

        return option;
    }
    
    const resolveButtonClass = (option) => {
        const classes = [props.size];
        if (isSelected(option)) {
            classes.push('selected');
        }
        return classes.join(' ');
    }
</script>

<template>
    <div class="button-group" :class="{'vertical': vertical}">
        <button v-for="(option, idx) in options" 
            :key="idx" 
            :class="resolveButtonClass(option)"
            @click="selectItem(option)"
        >
            <div class="inline-block"><input type="radio" :checked="isSelected(option)"></div>
            &nbsp;<slot :option="option">{{resolveLabel(option)}}</slot>
        </button>
    </div>
</template>

<style>
    .button-group {
        @apply flex;
    }
    .button-group button {
        @apply block;
        @apply px-3 py-1 border border-gray-300 focus:outline-none cursor-pointer;
        @apply border-r-0;
        @apply bg-gradient-to-b from-white to-gray-100 hover:to-gray-200;
        @apply active:from-gray-200 active:to-gray-100;
        @apply disabled:opacity-60 disabled:cursor-not-allowed;
    }

    .button-group button.xs {
        @apply text-xs px-2 py-0.5;
    }

    .button-group button.sm {
        @apply text-sm;
    }

    .button-group button.lg {
        @apply px-4 py-1.5 text-lg;
    }

    .button-group button.xl {
        @apply px-6 py-2 text-2xl;
    }


    .button-group button.selected {
        @apply bg-gradient-to-b from-gray-200 to-gray-50;
    }

    .button-group button:first-child {
        @apply rounded-l;
    }
    .button-group button:last-child {
        @apply rounded-r border-r;
    }

    .button-group.vertical {
        @apply flex-col;
    }

    .button-group.vertical button {
        @apply border-r border-b-0 text-left;
    }

    .button-group.vertical button:first-child{
        @apply rounded-t
    }
    .button-group.vertical button:last-child{
        @apply rounded-b border-b rounded-r-none
    }
</style>