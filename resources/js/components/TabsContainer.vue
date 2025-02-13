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
    @apply w-full overflow-x-auto;
}

</style>
<script>
import {isEqual} from 'lodash'

export default {
    name: 'TabsContainer',
    props: {
        tabLocation: {
            required: false,
            type: String,
            default: 'top'
        },
        modelValue: {
            required: false,
            type: Number,
            default: 0
        }
    },
    emits: [
        'update:modelValue',
        'tab-changed'
    ],
    data() {
        return {
            tabs: [],
        }
    },
    computed: {
        activeTab() {
            return this.tabs.find(t => t.active === true) || {};
        }
    },
    watch: {
        modelValue: function (newValue) {
            this.activateTab(newValue)
        },
        '$route.query': {
            immediate: true,
            handler: function (to, from) {
                if (!isEqual(to, from)) {
                    this.activateTabFromRoute();
                }
            }
        }
    },
    methods: {
        activateTab (idx) {
            this.tabs.forEach(t => t.active = false)
            this.tabs[idx].active = true
            this.$emit('update:modelValue', idx);
            this.$emit('tab-changed', this.tabs[idx].label);
            this.updateRouteQuery(this.tabs[idx].label);
        },

        activateTabWithLabel (routeLabel) {
            const normalized = routeLabel.toLowerCase();
            const idx = this.tabs
                .map(t => t.label.toLowerCase())
                .findIndex(label => label == normalized);
            if (idx > 0) {
                this.activateTab(idx);
            }
        },
        
        activateTabFromRoute () {
            
            const activeTabLabel = this.activeTab.label 
                                    ? this.activeTab.label.toLowerCase() 
                                    : null;

            if (
                activeTabLabel != this.$route.query.tab 
                && Object.keys(this.$route.query).includes('tab')
            ) {
                this.activateTabWithLabel(this.$route.query.tab);
            }
        },

        updateRouteQuery(label) {
            const newQuery = {...this.$route.query, ...{tab: label.toLowerCase()}};
            this.$router.replace({path: this.$route.path, query: newQuery})
        },

        setActiveIndex () {
            if (this.tabs.length > 0 && !this.modelValue) {
                this.tabs[0].active = true
            }
            if (this.modelValue && this.tabs.length > this.modelValue) {
                this.activateTab(this.modelValue);
            }
            if (Object.keys(this.$route.query).includes('tab')) {
                this.activateTabFromRoute();
            }
        },
        addTab (tab) {
            this.tabs.push(tab);
            this.setActiveIndex();
        },
        
        removeTab (tab) {
            const idx = this.tabs.findIndex(i => tab ==i)
            if (idx > -1) {
                this.tabs.splice(idx, 1);
            }
        },

        renderTabs () {
            return this.tabs
                .map((tab, idx) => {
                    if (!tab || !tab.visible) return

                    const tabClasses = ['tab', 'cursor-pointer'];
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
    mounted () {
        this.$emit('tab-changed', this.activeTab.label);
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
