<script>
import {ref, computed} from 'vue'
import {redeemInvite} from '@/domain/onboarding_service'
import { useStore } from 'vuex'
import ClerkSignUp from '@/components/ClerkSignUp.vue'

export default {
    name: 'AccountCreationForm',
    components: {
        ClerkSignUp
    },
    props: {
        invite: {
            type: Object,
            required: true
        }
    },
    emits: [
      'saved',
    ],
    setup (props, context) {
        const store = useStore();
        const submitting = ref(false);

        // After Clerk sign-up the user is authenticated; link that identity to
        // the invited person (creating the local account if needed).
        const onSignedUp = async () => {
            if (submitting.value) {
                return;
            }
            submitting.value = true;
            try {
                await redeemInvite(props.invite);
                await store.dispatch('forceGetCurrentUser');
                context.emit('saved');
            } finally {
                submitting.value = false;
            }
        }

        const alreadyHasAccount = computed(() => !!props.invite.person.user_id);

        return {
            email: props.invite.person.email,
            alreadyHasAccount,
            onSignedUp,
        }
    }
}
</script>
<template>
  <div>
    <div v-if="alreadyHasAccount">
      <static-alert>
        It looks like you've already activated your account. Please
        <router-link class="link" :to="{name: 'login', query: {redirect: '/'}}">
          sign in
        </router-link>
        to continue.
      </static-alert>
    </div>
    <div v-else>
      <p class="text-lg mb-2">
        Create your account
      </p>
      <ClerkSignUp :email="email" @signed-up="onSignedUp" />
    </div>
  </div>
</template>
