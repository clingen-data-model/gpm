<template>
    <div>
        <div v-if="mail.length == 0" class="well">
            {{person.first_name}} has not received any mail via the GPM.
        </div>
        <div class="w-3/4 my-4 p-4 border" v-for="email in mail" :key="email.id">
            <dictionary-row label="Date/Time">
                {{formatDate(email.created_at)}}
            </dictionary-row>
            <dictionary-row label="Subject">
                {{email.subject}}
            </dictionary-row>
            <dictionary-row label="Body">
                <div v-html="email.body"></div>
            </dictionary-row>
            <button class="btn btn-xs" @click.stop="initResend(email)" v-if="hasPermission('people-manage') || coordinatesPerson(person)">Resend</button>
        </div>
        <teleport to="body">
            <modal-dialog title="Resend Email" v-model="showResendDialog">
                <custom-email-form :mail-data="currentEmail" @sent="cleanupResend" @canceled="cleanupResend"></custom-email-form>
                button.btn.btn-xs[@click="getMail"]
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
export default {
    name: 'ComponentName',
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
            this.currentEmail = {},
            this.showResendDialog = false;
            this.$store.dispatch('people/getMail', this.person);
        },
    }
}
</script>