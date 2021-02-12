<template>
    <div class="absolute top-0 left-0 right-0 bottom-0 flex justify-center content-center" 
        @keyup.esc="close"
        v-show="isVisible"
    >
        <div 
            id="modal-backdrop" 
            class="absolute top-0 left-0 right-0 bottom-0 bg-black opacity-50" 
            @click="close"
        ></div>
        <div class="bg-white p-4 border border-gray-500 opacity-100 min-h-1/4 relative mt-24 mb-auto rounded-lg shadow-md" :class="width">
            <button @click="close" class="btn btn-xs gray float-right">X</button>
            <header>
                <slot name="header">
                    <h4>{{title}}</h4>
                </slot>
            </header>
            <slot name="default" />
        </div>
    </div>
</template>
<script>
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
        }
    },
    data() {
        return {
            isVisible: false
        }
    },
    watch: {
        modelValue(to) {
            this.isVisible = Boolean(to);
        }
    },
    computed: {
        width() {
            switch (this.size) {
                case 'sm': 
                    return 'md:w-1/3'
                case 'md':
                    return 'md:w-1/2'
                case 'lg': 
                    return 'md:w-2/3'
                case 'xl':
                    return 'md:w-3/4'           
                default:
                    return 'md:w-1/2'
            }
        }
    },
    methods: {
        open() {
            this.isVisible = true;
            this.$emit('opened');
            this.$emit('update:modelValue', this.isVisible);
        },
        close() {
            this.isVisible = false;
            this.$emit('closed');
            this.$emit('update:modelValue', this.isVisible);
        }
    }
}
</script>