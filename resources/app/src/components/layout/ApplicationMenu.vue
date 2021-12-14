<style lang="postcss" scoped>
  .app-menu {
    transition: all .75s ease-out;
    width: 25%;
    white-space: nowrap;
  }
  .app-menu.collapsed {
    transition: all .75s ease;
    width: 0px;
  }
</style>
<template>
    <div 
        :class="{collapsed: isCollapsed}"
        class="app-menu overflow-y-auto"
    >
        <ul class="menu-items">
            <li v-for="(item, idx) in menu" :key="idx">
                <menu-item :item="item" class="block"></menu-item>
            </li>
        </ul>
    </div>
</template>
<script>
import MenuItem from '@/components/layout/MenuItem'

export default {
    components: {
        MenuItem
    },
    props: {
        menu: {
            required: true,
            type: Object
        },
        isCollapsed: {
            required: false,
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>