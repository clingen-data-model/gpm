<template>
    <div>
        <h2 class="block-title">Are you sure you want to delete this application?</h2>
        <p>
            You are about to delete the application for <strong class="underline">{{application.name}}</strong>.
        </p>
        <p>This action cannot be undone.</p> 
        <p>Are you sure you want to continue?</p>

        <button-row 
            @submitted="commitDelete" 
            @canceled="cancelDelete" 
            submit-text="Delete Application" 
            submit-variant="red"
        ></button-row>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'

export default {
    name: 'ConfirmDeleteApplication',
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        }
    },
    watch: {
        application: function () {
            if (!this.application.uuid) {
                this.$store.dispatch('applications/getApplication', {appUuid: this.uuid});
            }
        }
    },
    methods: {
        cancelDelete() {
            this.$router.go(-1);
        },
        async commitDelete() {
            await this.$store.dispatch('applications/deleteApplication', {application: this.application});
            this.$router.push({name: 'ApplicationsIndex'})
        }
    }
}
</script>