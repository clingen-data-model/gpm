let handleOutsideClick;
export default {
    beforeMount (el, binding, vnode) {
        handleOutsideClick = (evt) => {
            evt.stopPropagation();
            const { handler, exclude } = binding.value
            const clickedExcluded = exclude.map(refName => binding.instance.$refs[refName])
                                            .includes(evt.target)
            if (!el.contains(evt.target) && !clickedExcluded) {
                handler()
            }

        }

        document.addEventListener('click', handleOutsideClick)
        document.addEventListener('touchstart', handleOutsideClick)
    },
    unmounted() {
        document.removeEventListener('click', this.handleOutsideClick)
        document.removeEventListener('touchstart', this.handleOutsideClick)
    },
}