<style lang="postcss" scoped>
    .dictionary-row {
        @apply my-2;
    }
</style>
<template>
    <div class="dictionary-row" :class="{'flex': !vertical}">
        <div :class="fullLabelClass">
            <slot name="label" v-if="label">
                <label>{{label}}{{colon}}</label>
            </slot>
        </div>
        <slot>
        </slot>
    </div>

</template>
<script>
export default {
    props: {
        label: {
            type: String,
            required: false
        },
        vertical: {
            type: Boolean,
            required: false,
            default: false
        },
        labelWidth: {
            type: Number,
            default: 9
        },
        labelClass: {
            type: String,
            required: false
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        colon () {
            if (this.label && [':',';','.','?', '!'].includes(this.label.substr(-1))) {
                return '';
            }
            return ':';    
        },
        fullLabelClass () {
            const classList = [];
            if (!this.vertical) {
                classList.push('w-'+(this.labelWidth*4).toString())
            }
            if (this.vertical) {
                classList.push('mb-1')
            }
            if (this.labelClass) {
                classList.push(this.labelClass.split(' '));
            }
            return classList
        }
    },
    methods: {

    }
}
</script>