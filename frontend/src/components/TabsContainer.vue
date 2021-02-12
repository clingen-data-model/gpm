<!-- <template>
    <div>
        <slot />
    </div>
</template> -->
<script>
import {h} from 'vue'

export default {
    name: 'TabsContainer',
    props: {
        
    },
    data() {
        return {
            tabs: []
        }
    },
    computed: {

    },
    methods: {
        activateTab (evt) {
            console.info('activateTab', evt)
        },
        addTab (tab) {
            this.tabs.push(tab)
        },
        renderTabs () {
            return this.tabs.map(item => {
                return h('li', {class: 'border rounded-t px-2 py-1', 'onClick': this.activateTab}, item.label)
            })
        },
    },
    render() {
        const tabList = this.renderTabs();
        return h('div', {}, [h('ul', {class: 'flex space-x-1'}, tabList), this.$slots.default()]);
    }
}
</script>