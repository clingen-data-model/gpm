<script>
export default {
    name: 'Collapsible',
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
        },
        showCheveron: {
            type: Boolean,
            default: true
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
<template>
  <div class="collapsible-container">
    <div class="collapsible-header" @click="expanded = !expanded">
      <div class="flex items-start">
        <div v-if="showCheveron">
          <icon-cheveron-right v-if="!expanded" class="-ml-1 mt-1" />
          <icon-cheveron-down v-if="expanded" class="-ml-1 mt-1" />
        </div>
        <slot name="title">
          <strong>{{ title }}</strong>
        </slot>
      </div>
    </div>
    <transition name="slide-fade-down">
      <div v-show="expanded">
        <slot />
      </div>
    </transition>
  </div>
</template>
