<script setup>
import { useRouter } from 'vue-router'
import DemographicsForm from '../components/people/DemographicsForm.vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    redirectTo: {
        type: Object,
        default: () => ({ name: 'Dashboard' })
    }
})
const router = useRouter();

const handleSave = async () => {
    try {

        await useAuthStore().forceGetCurrentUser();
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
      <DemographicsForm :uuid="useAuthStore().currentUser.person.uuid" :start-in-edit-mode="true" @saved="handleSave" />
    </card>
  </div>
</template>
