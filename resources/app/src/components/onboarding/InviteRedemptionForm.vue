<template>
    <div class="w-64 mx-auto">
        <label for="invite-code-input" class="text-lg block">
            Enter your registration code:
        </label>
        <input type="text"
            id="invite-code-input"
            v-model="inviteCode" 
            placeholder="XXXXXXXXXX" 
            class="w-full"
        >
        <input-errors :errors="errors"></input-errors>
        <button 
            class="btn blue btn-lg block mt-2 w-full" 
            @click="checkInvite"
        >
            Submit
        </button>
    </div>
</template>
<script>
import { ref, toRefs, onMounted, watch } from 'vue'
import {fetchInvite} from '@/domain/onboarding_service'
import InputErrors from '@/components/forms/InputErrors'
import isValidationError from '@/http/is_validation_error'

export default {
    name: 'InviteRedemptionCode',
    components: {
        InputErrors
    },
    props: {
        invite: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    data() {
        return {

        }
    },
    setup(props, context) {

        const {invite} = toRefs(props);
        let inviteCode = ref(null);
        let errors = ref([]);

        const syncCode = () => {
            inviteCode.value = props.invite.code
        }

        watch(invite, () => {
            syncCode()
        }, {
            deep: true
        })

        watch(inviteCode, (to) => {
            errors.value = [];
        });

        const checkInvite = async () => {
            try {
                const invite = await fetchInvite(inviteCode.value);
                context.emit('codeverified', invite);
            } catch (error) {
                if (error.response.status == 404) {
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
    computed: {

    },
    methods: {
    },
    mounted() {
        this.syncCode();
    }
}
</script>