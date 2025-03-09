<script>
export default {
    name: 'CoiAlert',
    props: {
        membership: {
            type: Object,
            required: true
        }
    },
    computed: {
        groupName () {
            return this.membership.group.display_name
        }
    }
}
</script>
<template>
    <static-alert
        class="pb-3 flex space-x-2 items-center"
        variant="warning"
    >
        <icon-coi height="30" width="30"></icon-coi>
        <div>
            <p>You have a pending <strong>COI</strong> disclosure for <strong>{{groupName}}</strong>.</p>
            <router-link
                :to="{
                    name: 'alt-coi',
                    params: {
                        name: groupName,
                        code: membership.group.coi_code
                    }
                }"
                class="btn font-bold"
            >
                Complete this COI Disclosure
            </router-link>
        </div>
    </static-alert>
</template>
