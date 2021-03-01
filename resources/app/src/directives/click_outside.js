let handleOutsideClick;
export default {
    beforeMount (el, binding, vnode) {
        console.log('binding outside-click')
        console.info('binding', binding)
        console.info('el', el)
        console.info('vnode', vnode)
        handleOutsideClick = (evt) => {
            evt.stopPropagation();
            const { handler, exclude } = binding.value
            const clickedOnExcludedElement = exclude.map(refName => binding.instance.$refs[refName])
                                            .includes(evt.target)
            if (!el.contains(evt.target) && !clickedOnExcludedElement) {
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