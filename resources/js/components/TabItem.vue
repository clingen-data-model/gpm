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
            return this.$parent.$options.name === 'TabsContainer'
        }
    },
    mounted() {
        this.$parent.addTab(this)
    },
    unmounted () {
        this.$parent.removeTab(this);
    },
    methods: {
        isActive() {
            return this.active;
        }
    }
}
</script>
<template>
    <keep-alive>
        <section v-if="active && visible">
            <slot></slot>
        </section>
    </keep-alive>
</template>