<script>
import CustomEmailForm from '@/components/mail/CustomEmailForm.vue';
export default {
    name: 'PersonMailLog',
    components: {
      CustomEmailForm,
    },
    props: {
        person: {
            type: Object,
            required: true
        },
        mail: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            currentEmail: {},
            showResendDialog: null,
        }
    },
    computed: {

    },
    methods: {
        initResend (email) {
            this.currentEmail = {...email};
            this.showResendDialog = true;
        },
        cleanupResend () {
            this.currentEmail = {}
            this.showResendDialog = false;
            this.$store.dispatch('people/getMail', this.person);
        },
    }
}
</script>
<template>
  <div>
    <div v-if="mail.length == 0" class="well">
      {{ person.first_name }} has not received any mail via the GPM.
    </div>
    <div v-for="email in mail" :key="email.id" class="w-3/4 my-4 p-4 border">
      <span class="text-gray-600">Sent at</span> {{ formatDate(email.created_at) }}
      <div><span class="text-gray-600">To:</span> {{ email.to.map(i => i.address).join(', ') }}</div>
      <div v-if="email.cc">
        <span class="text-gray-600">CC:</span> {{ email.cc.map(i => i.address).join(', ') }}
      </div>
      <div v-if="email.bcc">
        <span class="text-gray-600">BCC:</span> {{ email.bcc.map(i => i.address).join(', ') }}
      </div>
      <div class="mt-1">
        <span class="text-gray-600">Subject:</span> {{ email.subject }}
      </div>
      <hr>
      <div v-html="email.body" />
      <hr>
      <button
        v-if="hasPermission('people-manage') || coordinatesPerson(person)"
        class="btn btn-xs"
        @click.stop="initResend(email)" 
      >
        Resend
      </button>
    </div>
    <teleport to="body">
      <modal-dialog v-model="showResendDialog" title="Resend Email">
        <CustomEmailForm :mail-data="currentEmail" @sent="cleanupResend" @canceled="cleanupResend" />
      </modal-dialog>
    </teleport>
  </div>
</template>