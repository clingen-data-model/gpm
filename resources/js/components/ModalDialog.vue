<script>
import {nextTick} from 'vue'
export default {
    props: {
        title: {
            type: String,
            required: false,
        },
        modelValue: {
            type: Boolean,
            required: false,
            default: false
        },
        size: {
            type: String,
            required: false,
            default: 'md'
        },
        hideClose: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: [
        'opened',
        'closed',
        'update:modelValue'
    ],
    data() {
        return {
        }
    },
    computed: {
        panelStyle () {
            return {
                maxHeight: '500px',
            }
        },
        isVisible: {
            immediate: true,
            get () {
                return this.modelValue
            },
            set (value) {
                this.$emit('update:modelValue', value)
            }
        },
        width() {
            switch (this.size) {
                case 'xxs':
                    return 'lg:w-1/6'
                case 'xs':
                    return 'lg:w-1/4'
                case 'sm':
                    return 'lg:w-1/3'
                case 'md':
                    return 'lg:w-1/2'
                case 'lg':
                    return 'lg:w-2/3'
                case 'xl':
                    return 'lg:w-3/4'
                case 'xxl':
                    return 'lg:w-11/12'
                case 'full':
                    return 'w-full'
                default:
                    return 'lg:w-1/2'
            }
        },
        hasHeader () {
            return (this.$slots.header || this.title);
        },
        modalClass () {
            const classes = [
                'bg-white',
                'border',
                'border-gray-500',
                'opacity-100',
                'relative',
                'mt-16',
                'mb-auto',
                'rounded-lg',
                'shadow-md',
                this.width
            ];
            return classes.join(' ');
        }
    },
    watch: {
        async isVisible (to) {
            document.body.style.overflow = "auto"
            if (to) {
                await nextTick();
                this.setBodyMaxHeight()
                document.body.style.overflow = "hidden"
            }
        }
    },
    mounted () {
        this.setBodyMaxHeight();
    },
    methods: {
        open() {
            this.isVisible = true;
            this.$emit('opened');
        },
        close() {
            this.isVisible = false;
            this.$emit('closed');
        },
        setBodyMaxHeight () {
            const bodyBoundingRect = this.$refs.panelbody.getBoundingClientRect();
            // const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0)
            this.$refs.panelbody.style.maxHeight = `calc(90vh - ${(bodyBoundingRect.y)}px)`
        },
    }
}
</script>
<template>
  <div
    v-show="isVisible"
    class="fixed top-0 left-0 right-0 bottom-0 flex justify-center content-center z-50"
    @keyup.esc="close"
  >
    <div
      id="modal-backdrop"
      class="fixed top-0 left-0 right-0 bottom-0 bg-black opacity-50"
      @click="close"
    />

    <div
      :class="modalClass"
    >
      <header class="flex border-b pt-2 pb-2 justify-between items-center">
        <div class="px-4 pt-2">
          <slot name="header">
            <h2 v-if="title" class="">
              {{ title }}
            </h2>
          </slot>
        </div>
        <button
          v-if="!hideClose" class="bock btn btn-xs gray mx-2"
          @click="close"
        >
          X
        </button>
      </header>

      <section ref="panelbody" class="overflow-auto px-4 pb-2 pt-2">
        <slot name="default" />
      </section>
      <div v-if="$slots.footer" class="footer px-4 py-2">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>
