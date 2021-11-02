<template>
    <div class="collapsible-container">
        <div class="collapsible-header" @click="expanded = !expanded">
            <div class="flex items-center">
                <right-cheveron v-if="!expanded" class="-ml-1"></right-cheveron>
                <down-cheveron v-if="expanded" class="-ml-1"></down-cheveron>
                <slot name="title">
                    <strong>{{title}}</strong>
                </slot>
            </div>
        </div>
        <transition name="slide-fade-down">
            <div v-show="opened">
                <slot></slot>
            </div>
        </transition>
    </div>
</template>
<script>
import DownCheveron from '@/components/icons/IconCheveronDown'
import RightCheveron from '@/components/icons/IconCheveronRight'

export default {
    name: 'Collapsible',
    components: {
        DownCheveron,
        RightCheveron
    },
    props: {
        modelValue: {
            type: Boolean,
            required: false,
            default: null
        },
        title: {
            type: String,
            required: false,
            default: null,
        }
    },
    emits: [
        'expanded',
        'collapsed',
        'update:modelUpdate'
    ],
    data() {
        return {
            opened: false
        }
    },
    computed: {
        valueSet () {
            return this.modelValue !== null;
        },
        expanded: {
            get () {
                return this.valueSet ? this.modelValue : this.opened;
            },
            set (value) {
                this.opened = value;
                this.$emit('update:modelUpdate', value);

                if (this.opened) {
                    this.$emit('expanded');
                } else {
                    this.$emit('collapsed');
                }
            }
        }
    },
    methods: {

    }
}
</script>