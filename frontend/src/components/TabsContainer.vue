<script>

export default {
    name: 'TabsContainer',
    props: {
        
    },
    data() {
        return {
            tabs: [],
            selectedTab: 0
        }
    },
    computed: {

    },
    methods: {
        activateTab (idx) {
            this.tabs.forEach(t => t.active = false)
            this.tabs[idx].active = true
        },
        setDefaultActiveTab () {
            if (this.tabs.findIndex(t => t.active) < 0) {
                this.tabs[0].active = true;
            }
        },
        addTab (tab) {
            this.tabs.push(tab);
            this.setDefaultActiveTab();
        },
        renderTabs () {
            return this.tabs.map((tab, idx) => {
                if (!tab) return

                const tabClasses = ['tab'];
                if (tab.active) {
                    tabClasses.push('active');
                }

                return (
                    <li class={tabClasses.join(' ')} onClick={ () => this.activateTab(idx) }>
                        {tab.label}
                    </li>
                )
            })
        }
    },
    render() {
        const tabList = this.renderTabs();
        return (
            <div>
                <ul class="tabs">
                    {tabList}
                </ul>
                <div class="p-4 border rounded-tr-lg rounded-b-lg bg-white">
                    {this.$slots.default()}
                </div>
            </div>
        )
    }
}
</script>