<script setup>
    import {computed, ref} from 'vue'
    import {useRouter} from 'vue-router'
    import {useStore} from 'vuex'
    import ProfileForm from '../components/people/ProfileForm.vue';

    const store = useStore();
    const router = useRouter();

    const props = defineProps({
        redirectTo: {
            type: Object
        }
    })

    const user = computed(() => store.getters.currentUser || {person: {memberships: []}})
    const legacyExpertise = computed(() => {
        return user.value.person.memberships
            .map(m => ({groupName: m.group.name,  legacyExpertise: m.legacy_expertise}))
            .filter(lc => lc.legacyExpertise !== null)
    })

    const handleSaved = async () => {
        try {
            await store.dispatch('forceGetCurrentUser');
            if (!props.redirectTo) {
                router.replace({name: 'Dashboard'});
                return;
            }
            router.replace(props.redirectTo)
        } catch (e) {
            // eslint-disable-next-line no-console
            console.log(e);
        }
    }

    const showMoreInfo = ref(false)
</script>
<template>
    <div>
        <div class="border-bottom pb-2 rounded-lg mb-4 w-3/4">
            <h2>Please review your profile</h2>
            <collapsible v-model="showMoreInfo" :showCheveron="false">
                <template #title>
                    We've changed the way we collect information about your credentials and expertise.  Please help us out by updating this information.
                </template>
                <p>
                    <strong>Credentials:</strong> We are now collecting credentials as selections from a controlled vocabulary.  Credentials indicate the degree or official certifications you hold.
                </p>

                <p>
                    <strong>Expertise:</strong> Expert panels collect the "expertise" of their members so ClinGen can ensure the necessary domains and skill sets are represented.  Previously, expertise was added for you for each group you were a part of.  We are now collecting your expertise once and it will be used for all expert panels of which you are a part.
                </p>
            </collapsible>
            <button class="link text-sm" @click="showMoreInfo = !showMoreInfo">
                {{showMoreInfo ? 'Show Less' : 'More Info' }}
            </button>
            <br>
            <div v-if="user.needsCredentials && user.person.legacy_credentials">
                <strong>Your old credential info:</strong><br>
                {{user.person.legacy_credentials}}
            </div>
            <div v-if="user.needsExpertise && legacyExpertise.length > 0">
                <strong>Your old experise info:</strong>
                <ul class="list-disc pl-6">
                    <li v-for="(m) in legacyExpertise" :key="m.groupName">
                        <strong>{{m.groupName}}:</strong> {{m.legacyExpertise}}
                    </li>
                </ul>
            </div>
        </div>
        <ProfileForm
            :person="store.getters.currentUser.person"
            :showTitle="false"
            @saved="handleSaved"
        />
    </div>
</template>
