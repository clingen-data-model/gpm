<script setup>
    import { useStore } from 'vuex'
    const store = useStore();
</script>

<template>
    <card title="You have Conflict of Interest Disclosures to Complete">
        <h3>You must complete a Conflict of Interest Disclosure for the following memberships:</h3>
        <div class="my-2">
            <router-link
                v-for="membership in store.getters.currentUser.person.membershipsWithPendingCois"
                :key="membership.id"
                class="block my-0 font-bold p-2 border border-gray-300 first:rounded-t-lg last:rounded-b-lg cursor-pointer hover:bg-blue-50 link"
                :to="`/coi/${membership.group.expert_panel.coi_code}`"
            >
                {{membership.group.expert_panel.display_name}}
            </router-link>
        </div>
        <!-- <ul class="list-disc pl-6">
            <li v-for="membership in " :key="membership.id">
                <a :href="`/coi/${membership.group.expert_panel.coi_code}`">
                    {{membership.group.expert_panel.display_name}}
                </a>
            </li>
        </ul> -->
    </card>
</template>
