<script setup>
    import {useRouter} from 'vue-router'
    import {useStore} from 'vuex'
    import ProfileForm from '../components/people/ProfileForm.vue';

    const props = defineProps({
        redirectTo: {
            type: Object,
            default: () => ({name: 'Dashboard'})
        }
    })
    const store = useStore();
    const router = useRouter();

    const handleSave = async () => {
        await store.dispatch('forceGetCurrentUser');

        router.replace(props.redirectTo)
    }

</script>
<template>
  <div>
    <card title="Please fill out your profile">
      <ProfileForm
        :person="store.getters.currentUser.person"
        :allowCancel="false"
        :showTitle="false"
        @saved="handleSave"
      />
    </card>
  </div>
</template>
