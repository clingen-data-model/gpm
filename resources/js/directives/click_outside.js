let handleOutsideClick;
export default {
    beforeMount (el, binding) {
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
        document.removeEventListener('click', handleOutsideClick)
        document.removeEventListener('touchstart', handleOutsideClick)
    },
}