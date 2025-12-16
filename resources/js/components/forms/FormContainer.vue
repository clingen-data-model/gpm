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
    mounted() {
        this.visibilityInterval = setInterval(this.checkVisibility, 100);
    },
    unmounted() {
        clearInterval(this.visibilityInterval);
    },
    methods: {
        checkVisibility() {
            this.visibility = this.$el && (this.$el.offsetWidth > 0 || this.$el.offsetHeight > 0);
        },
        focusFirstInput() {
            if (!this.$el) return;
            const inputs = this.$el.querySelectorAll('input, textarea, select');
            if (!inputs.length) return;
            inputs[0].focus();
        }
    }
}
</script>
<template>
  <div>
    <slot />
  </div>
</template>   