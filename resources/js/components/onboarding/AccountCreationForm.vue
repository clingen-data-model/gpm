<script setup>
import { onMounted, ref, watch } from 'vue'
import { useStore } from 'vuex'
import { redeemInvite, redeemInviteForExistingUser, redeemInviteWithIdp } from '@/domain/onboarding_service'
import isValidationError from '@/http/is_validation_error'
import LoginForm from '@/components/LoginForm.vue'
import IdpSignIn from '@/components/auth/IdpSignIn.vue'
import { useIdp } from '@/idp'

/**
 * Invite wizard step that gives the invited person a GPM login. With an
 * identity provider configured they may either create a password (which is
 * mirrored to the IdP) or sign in with the ClinGen account they already
 * have; the latter is linked explicitly by clicking Continue, never
 * automatically, because a coordinator's own IdP session may be active in
 * the browser.
 */
const props = defineProps({
    invite: {
        type: Object,
        required: true,
    },
})
const emit = defineEmits(['saved'])

const store = useStore()
const idp = useIdp()
const idpEnabled = idp.driver !== 'null'

const errors = ref({})
const email = ref(null)
const password = ref(null)
const password_confirmation = ref(null)

// 'password' | 'idp' | null (choice not made yet)
const mode = ref(idpEnabled ? null : 'password')
const idpSignedIn = ref(false)
const idpError = ref(null)
const linking = ref(false)

const createAccount = async () => {
    try {
        await redeemInvite(props.invite, email.value, password.value, password_confirmation.value)
        await store.dispatch('login', { email: email.value, password: password.value })
        emit('saved')
    } catch (error) {
        if (isValidationError(error)) {
            errors.value = error.response.data.errors
        }
    }
}

const redeemForExistingUser = async () => {
    try {
        await redeemInviteForExistingUser(props.invite)
        emit('saved')
    } catch (error) {
        if (isValidationError(error)) {
            errors.value = error.response.data
        }
    }
}

const continueWithIdp = async () => {
    if (linking.value) {
        return
    }
    linking.value = true
    idpError.value = null
    try {
        const token = await idp.getToken()
        if (!token) {
            throw new Error('The identity provider did not return a session token.')
        }
        await redeemInviteWithIdp(props.invite, token)
        store.commit('setAuthenticated', true)
        await store.dispatch('forceGetCurrentUser')
        emit('saved')
    } catch (e) {
        const data = e?.response?.data
        idpError.value = data?.errors?.email?.[0]
            || data?.errors?.code?.[0]
            || data?.message
            || e?.message
            || 'You are signed in with the identity provider, but the invite could not be linked to that account.'
    } finally {
        linking.value = false
    }
}

const signOutOfIdp = async () => {
    idpError.value = null
    await idp.signOut()
    idpSignedIn.value = false
}

const syncEmail = () => {
    email.value = props.invite.person.email
}

onMounted(() => syncEmail())

watch(idp.isSignedIn, (signedIn) => {
    idpSignedIn.value = Boolean(signedIn)
}, { immediate: true })
</script>
<template>
  <div>
    <div v-if="invite.person.user_id">
      <static-alert>
        It looks like you've already activated you account.  Please login to continue.
      </static-alert>
      <LoginForm @authenticated="redeemForExistingUser" />
    </div>
    <div v-else>
      <div v-if="idpEnabled && mode === null" class="space-y-3">
        <p class="text-lg">
          How would you like to sign in to the GPM?
        </p>
        <div class="flex flex-col md:flex-row gap-3">
          <button class="btn blue flex-1" name="choose-idp" @click="mode = 'idp'">
            I already have a ClinGen account
          </button>
          <button class="btn flex-1" name="choose-password" @click="mode = 'password'">
            Create a password
          </button>
        </div>
        <p class="text-sm text-gray-600">
          ClinGen accounts are shared with other ClinGen applications. If you sign in to any of them already,
          use that account here and you will not need a separate GPM password.
        </p>
      </div>

      <div v-else-if="mode === 'idp'">
        <p class="text-lg mb-2">
          Sign in with your ClinGen account
        </p>
        <div v-if="linking" class="alert alert-info mb-4">
          Linking your account…
        </div>
        <div v-else-if="idpError" class="alert alert-danger mb-4">
          <p>{{ idpError }}</p>
          <button class="btn btn-xs mt-2" name="idp-sign-out" @click="signOutOfIdp">
            Sign out of the identity provider
          </button>
        </div>

        <div v-if="idpSignedIn && !idpError" class="border rounded p-3 mb-3 bg-gray-50">
          <p class="mb-2">
            You are signed in with a ClinGen account. Continue to link it to this invitation for
            <strong>{{ invite.person.first_name }} {{ invite.person.last_name }}</strong>.
          </p>
          <div class="flex items-center gap-3">
            <button class="btn blue" name="idp-continue" :disabled="linking" @click="continueWithIdp">
              Continue as the signed-in account
            </button>
            <button class="link text-sm" name="idp-not-you" @click="signOutOfIdp">
              Not you? Sign out
            </button>
          </div>
        </div>
        <IdpSignIn v-else-if="!linking" @signed-in="idpSignedIn = true" />

        <div class="mt-4 border-t pt-3 text-sm">
          <button class="link" name="choose-password-instead" @click="mode = 'password'; idpError = null">
            Create a GPM password instead
          </button>
        </div>
      </div>

      <div v-else>
        <p class="text-lg">
          Create your account
        </p>
        <input-row
          v-model="email"
          label="Email"
          :errors="errors.email"
          label-width-class="w-24"
        />
        <input-row
          v-model="password"
          label="Password" type="password"
          :errors="errors.password"
          label-width-class="w-24"
        />
        <input-row
          v-model="password_confirmation"
          label="Confirm Password" type="password"
          :errors="errors.password"
          label-width-class="w-24"
        />
        <div class="flex flex-row-reverse items-center gap-3">
          <button class="btn blue" @click="createAccount">
            Next
          </button>
          <button v-if="idpEnabled" class="link text-sm" name="choose-idp-instead" @click="mode = 'idp'">
            I already have a ClinGen account
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
