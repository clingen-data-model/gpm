/**
 * Simple remaining height directive for vuejs.
 *
 * Registration:
 * import remainingHeight from 'path/to/js'
 * V2: Vue.directive(remainingHeight)
 * V3: vue.createApp().directive(remainingHeight)
 *
 * Usage:
 * <div v-remaining-height="500">...</div>
 *
 * Accepts a integer value to set the offset.
 */
export default {
    // For Vue3
    mounted (el, binding) {
        const rect = el.getBoundingClientRect();
        const elementOffset = rect.top;
        const offset = (binding.value) ? binding.value : (elementOffset+36);
        el.style.height = `calc(100vh - ${offset}px)`
        el.style.overflow = 'auto'
    },
    // For Vue2
    bind (el, binding) {
        const rect = el.getBoundingClientRect();
        const elementOffset = rect.top;
        const offset = (binding.value) ? binding.value : (elementOffset+36);
        el.style.height = `calc(100vh - ${offset}px)`
        el.style.overflow = 'auto'
    }
}
