<template>
    <div class="collapsible-container">
        <div class="collapsible-header" @click="expanded = !expanded">
            <slot name="title">
                <div class="flex">
                    <right-cheveron v-if="!expanded" class="-ml-1"></right-cheveron>
                    <down-cheveron v-if="expanded"></down-cheveron>
                    <strong>{{title}}</strong>
                </div>
            </slot>
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
        'collpsed'
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
                return this.valueSet ? this.value : this.opened;
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