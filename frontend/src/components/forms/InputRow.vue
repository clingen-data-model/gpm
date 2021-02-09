<template>
    <div :class="{'border border-red-500 rounded-sm p-2 mb-2': hasErrors}">
        <input-errors :errors="errors"></input-errors>
        <div class="flex my-3">
            <div class="w-36">
                <slot name="label" v-if="label">
                    <label :class="{'text-red-800': hasErrors}">{{label}}:</label>
                </slot>
            </div>
            <slot></slot>
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
        errors: {
            type: Array,
            required: false,
            default: () => []
        },
        label: {
            type: String,
            required: false
        }
    },
    computed: {
        hasErrors () {
            return this.errors.length > 0;
        }
    }
}
</script>