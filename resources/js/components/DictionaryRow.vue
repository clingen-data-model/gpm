<script>
export default {
    name: 'DictionaryRow',
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
        labelWidthClass: {
            type: String,
            default: 'w-36'
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
            if (this.vertical) {
                classList.push('mb-1')
            }
            if (this.labelClass) {
                classList.push(this.labelClass.split(' '));
            }

            if (!this.vertical) {
                classList.push(this.labelWidthClass || 'w-36');
            }
            return classList
        }
    },
    methods: {

    }
}
</script>
<template>
    <div class="dictionary-row" :class="{'flex': !vertical}">
        <div 
            :class="fullLabelClass"
            class="flex-shrink-0" 
        >
            <slot name="label" v-if="label">
                <label :class="fullLabelClass">{{label}}{{colon}}</label>
            </slot>
        </div>
        <div>
            <slot>
            </slot>
        </div>
        <div class="hidden w-36">&nbsp;</div>
    </div>

</template>
<style lang="postcss" scoped>
    .dictionary-row {
        @apply my-2;
    }
</style>