<script>
export default {
    name: 'TabItem',
    props: {
        label: {
            required: true,
            type: String
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
        console.log('mounted!')
    },
}
</script>
<template>
    <keep-alive>
        <section v-if="this.active">
            <slot></slot>
        </section>
    </keep-alive>
</template>