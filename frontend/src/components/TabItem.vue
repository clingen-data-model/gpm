<!-- <template>
    <div>
        {{label}}
        <slot />
    </div>
</template> -->
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
            
        }
    },
    computed: {
        parentIsValid() {
            return this.$parent.$options.name == 'TabsContainer'
        }
    },
    methods: {
    },
    beforeCreate() {
        console.log('beforeCrate TabItem')
    },
    created() {
        console.log('created TabItem')
    },
    beforeMount() {
        console.log('beforeMount TabItem')
    },
    mounted() {
        console.log('tabItem mounted. add tab to parent');
        this.$parent.addTab(this)
    }
}
</script>