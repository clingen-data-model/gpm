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
            return this.$parent.$options.name === 'DropdownMenu'
        }
    },
    mounted() {
        // this.$parent.addItem(this)
    },
}
</script>
<template>
    <li class="menu-item hover:bg-blue-100 cursor-pointer">
        <slot></slot>
    </li>
</template>