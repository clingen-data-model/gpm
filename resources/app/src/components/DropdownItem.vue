<script>
export default {
    name: 'DropdownItem',
    data() {
        return {
            active: false   
        }
    },
    computed: {
        parentIsValid() {
            return this.$parent.$options.name == 'DropdownMenu'
        }
    },
    mounted() {
        // this.$parent.addItem(this)
    },
    render() {
        return (
            <li class="menu-item hover:bg-blue-100 cursor-pointer">
                {this.$slots.default()}
            </li>
        )
    }
}
</script>