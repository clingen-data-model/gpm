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
    methods: {
        cancelDelete() {
            this.$router.go(-1);
        },
        async commitDelete() {
            this.$store.dispatch('groups/delete', this.group.uuid);
            this.$store.commit('pushSuccess', 'Application deleted.');
            this.$router.push({name: 'ApplicationsIndex'});
        }
    }
}
</script>
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