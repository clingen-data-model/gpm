<script setup>
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import DemographicsForm from '../components/people/DemographicsForm.vue';

const props = defineProps({
    redirectTo: {
        type: Object,
        default: () => ({ name: 'Dashboard' })
    }
})
const store = useStore();
const router = useRouter();

const handleSave = async () => {
    try {

        await store.dispatch('forceGetCurrentUser');
        router.replace(props.redirectTo);

    } catch (error) {
        console.error('Error updating user:', error);
        // Handle the error appropriately (e.g., display an error message)
    }
}


</script>
<template>
  <div>
    <card title="Please fill out your demographic profile information">
      <DemographicsForm :uuid="store.getters.currentUser.person.uuid" :start-in-edit-mode="true" @saved="handleSave" />
    </card>
  </div>
</template>
