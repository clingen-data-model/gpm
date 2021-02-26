<script>
export default {
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
            return (
                <section>{this.$slots.default()}</section>
            )
        }
        return null;
    }
}
</script>