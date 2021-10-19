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
    },
    render() {
        if (this.isActive()) {
            if (this.$slots.default) {
                return (
                    <section>{this.$slots.default()}</section>
                )
            }
            return (<section></section>)
        }
        return null;
    }
}
</script>