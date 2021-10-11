export default {
    mounted (el, binding) {
        const rect = el.getBoundingClientRect();
        const elementOffset = rect.top;
        let offset = (binding.value) ? binding.value : (elementOffset+36);
        console.log({elementOffset, offset});
        el.style.height = `calc(100vh - ${offset}px)`
        el.style.overflow = 'auto'       
    }
}