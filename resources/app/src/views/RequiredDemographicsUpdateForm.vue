<script setup>
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import DemographicsForm from '../components/people/DemographicsForm.vue';

const store = useStore();
const router = useRouter();

const props = defineProps({
    redirectTo: {
        type: Object,
        default: () => ({ name: 'Dashboard' })
    }
})

const handleSave = async () => {
    try {
        console.log('forceGetCurrentUser action initiated');
        await store.dispatch('forceGetCurrentUser');
        console.log('forceGetCurrentUser action completed');
        console.log('Redirection target:', props.redirectTo);
        router.replace(props.redirectTo);
        console.log('Redirection successful');
    } catch (error) {
        console.error('Error updating user:', error);
        // Handle the error appropriately (e.g., display an error message)
    }
}


</script>
<template>
    <div>
        <card title="Please fill out your demographic profile information">
            <DemographicsForm :uuid="store.getters.currentUser.person.uuid" @saved="handleSave" v-bind:is-new="true" />
        </card>
    </div>
</template>