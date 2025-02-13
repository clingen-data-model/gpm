<template>
    <div>
        <slot></slot>
    </div>
</template>
<script>
export default {
    props: {
        
    },
    data() {
        return {
            visibility: false,
            visibilityInterval: null,
        }
    },
    watch: {
        visibility(to, from) {
            if (to && !from) {
                this.focusFirstInput();
            }
        }
    },
    methods: {
        checkVisibility() {
            this.visibility = this.$el && (this.$el.offsetWidth > 0 || this.$el.offsetHeight > 0);
        },
        focusFirstInput() {
            this.$el.querySelectorAll("input, textarea, select")[0].focus()
        }
    },
    mounted() {
        this.visibilityInterval = setInterval(this.checkVisibility, 100);
    },
    unmounted() {
        clearInterval(this.visibilityInterval);
    }
}
</script>   