<script setup>
    import {useStore} from 'vuex'
    import {useRouter} from 'vue-router'
    import DemographicsFormOptionsFinal from '../components/people/DemographicsFormOptionsFinal.vue';

    const store = useStore();
    const router = useRouter();

    const props = defineProps({
        redirectTo: {
            type: Object,
            default: () => ({name: 'Dashboard'})
        }
    })

    const handleSave = async () => {
        await store.dispatch('forceGetCurrentUser');

        router.replace(props.redirectTo)
    }

</script>
<template>
    <div>
        <card title="Please fill out your demographic profile information">
            <DemographicsFormOptionsFinal
                :uuid="store.getters.currentUser.person.uuid"
                @saved="handleSave"
            />
        </card>
    </div>
</template>
