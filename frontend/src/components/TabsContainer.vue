<style lang="postcss">
.horizontal > .tabs {
    @apply flex space-x-2
}

.horizontal > .tabs > .tab {
    @apply border border-b-0 px-4 py-1 rounded-t-lg bg-gray-200 -mb-px;
}

.horizontal>.tabs>.tab.router-link-active,
.horizontal>.tabs>.tab.active {
    @apply bg-white no-underline;
}

.horizontal > .tab-content {
    @apply p-4 border rounded-tr-lg rounded-b-lg bg-white
}

.vertical > .tabs {
    @apply flex-none mr-4 space-y-2;
}

.vertical > .tabs > .tab {
    @apply border rounded px-4 py-1 bg-gray-200
}

.vertical>.tabs>.tab.router-link-active,
.vertical>.tabs>.tab.active {
    @apply bg-blue-500 text-white;
}

.vertical > .tab-content {
    @apply w-full
}

</style>
<script>

export default {
    name: 'TabsContainer',
    props: {
        tabLocation: {
            required: false,
            type: String,
            default: 'top'
        }
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
        },
    },
    render() {
        const tabList = this.renderTabs();
        const containerClass = [];
        const tabsClass = ['tabs'];
        if (this.tabLocation == 'top') {
            containerClass.push('horizontal')
        }
        if (this.tabLocation == 'right') {
            containerClass.push('vertical')
            containerClass.push('flex')
        }

        return (
            <div class={containerClass.join(' ')}>
                <ul class={tabsClass.join(' ')}>
                    {tabList}
                </ul>
                <div class="tab-content">
                    {this.$slots.default()}
                </div>
            </div>
        )
    }
}
</script>
