<script>
export default {
    name: 'TabItem',
    props: {
        label: {
            required: true,
            type: String
        },
        visible: {
            type: Boolean,
            required: false,
            default: true
        }
    },
    data() {
        return {
            active: false   
        }
    },
    computed: {
        parentIsValid() {
            return this.$parent.$options.name == 'TabsContainer'
        }
    },
    methods: {
        isActive() {
            return this.active;
        }
    },
    mounted() {
        this.$parent.addTab(this)
    },
    unmounted () {
        this.$parent.removeTab(this);
    }
}
</script>
<template>
    <keep-alive>
        <section v-if="this.active && this.visible">
            <slot></slot>
        </section>
    </keep-alive>
</template>