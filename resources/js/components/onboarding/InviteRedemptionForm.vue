<script>
import { ref, toRefs, watch } from 'vue'
import {fetchInvite} from '@/domain/onboarding_service'
import InputErrors from '@/components/forms/InputErrors.vue'
import isValidationError from '@/http/is_validation_error'

export default {
    name: 'InviteRedemptionCode',
    components: {
        InputErrors
    },
    props: {
        code: {
            required: false
        },
        invite: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    emits: [
        'codeverified',
    ],
    setup(props, context) {

        const {invite} = toRefs(props);
        const inviteCode = ref(null);
        const errors = ref([]);

        const syncCode = () => {
            inviteCode.value = props.invite.code
        }

        watch(invite, () => {
            syncCode()
        }, {
            deep: true
        })

        watch(inviteCode, () => {
            errors.value = [];
        });

        const checkInvite = async () => {
            try {
                const invite = await fetchInvite(inviteCode.value);
                context.emit('codeverified', invite);
            } catch (error) {
                if (Number.parseInt(error.response.status) === 404) {
                    errors.value = ['The code you entered is not valid'];
                    return;
                }
                if (isValidationError(error)) {
                    errors.value = error.response.data.errors.code
                }
            }
        }

        return {
            inviteCode,
            errors,
            checkInvite,
            syncCode,
        }

    },
    data() {
        return {

        }
    },
    computed: {

    },
    mounted() {
        this.syncCode();
    },
    beforeMount () {
        if (this.$store.getters.currentUser.id !== null) {
            this.$store.commit('pushError', 'You can\'t redeem an invite b/c you\'re already logged in.');
            this.$router.replace({name: 'Dashboard'})
        }
    },
    methods: {
    }
}
</script>
<template>
  <div class="w-64 mx-auto">
    <label for="invite-code-input" class="text-lg block">
      Enter your registration code:
    </label>
    <input
      id="invite-code-input"
      v-model="inviteCode"
      type="text"
      placeholder="XXXXXXXXXX"
      class="w-full"
    >
    <InputErrors :errors="errors" />
    <button
      class="btn blue btn-lg block mt-2 w-full"
      @click="checkInvite"
    >
      Submit
    </button>
  </div>
</template>
