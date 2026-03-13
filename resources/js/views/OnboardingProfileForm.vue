<script setup>
    import {useRouter} from 'vue-router'
    import ProfileForm from '../components/people/ProfileForm.vue';
    import { useAuthStore } from '@/stores/auth';

    const props = defineProps({
        redirectTo: {
            type: Object,
            default: () => ({name: 'Dashboard'})
        }
    })
    const router = useRouter();

    const handleSave = async () => {
        await useAuthStore().forceGetCurrentUser();

        router.replace(props.redirectTo)
    }

</script>
<template>
  <div>
    <card title="Please fill out your profile">
      <ProfileForm
        :person="useAuthStore().currentUser.person"
        :allow-cancel="false"
        :show-title="false"
        @saved="handleSave"
      />
    </card>
  </div>
</template>
