<template>
    <breadcrumbs :items="items"></breadcrumbs>
</template>
<script>
import {Group} from '@/domain'

export default {
    name: 'GroupBreadcrumbs',
    props: {
        group: {
            required: false,
            default: () => {
                return new Group();
            }
        }
    },
    computed: {
        items () {
            const items = [
                {route: {name: 'GroupList'}, label: 'Groups'},
            ]
            if (this.group.uuid) {
                items.push({
                    route: {name: 'GroupDetail', params: {uuid: this.group.uuid}},
                    label: this.group.displayName
                })
            }
            return items;
        }
    },
    methods: {

    }
}
</script>