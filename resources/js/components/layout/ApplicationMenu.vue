<script>
import MenuItem from '@/components/layout/MenuItem.vue'

export default {
    components: {
        MenuItem
    },
    props: {
        application: {
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
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        }
        
    },
    methods: {
        isCurrentStep(item) {
            return item.name === this.application.getCurrentStep(this.group).name
        }
    }
}
</script>
<template>
    <div 
        :class="{collapsed: isCollapsed}"
        class="app-menu overflow-y-auto relative"
    >
        <ul class="menu-items">
            <li v-for="(item, idx) in application.steps" :key="idx">
                <MenuItem :item="item" class="block" :is-current-step="isCurrentStep(item)"></MenuItem>
            </li>
        </ul>
        <div class="">
            <slot name="footer">

            </slot>
        </div>
    </div>
</template>
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