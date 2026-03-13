<script>
import { useGroupsStore } from '@/stores/groups'
import { useAlertsStore } from '@/stores/alerts'

export default {
    name: 'ConfirmDeleteApplication',
    props: {
        uuid: {
            required: true,
            type: String
        }
    },
    setup() {
        return { groupsStore: useGroupsStore(), alertsStore: useAlertsStore() }
    },
    data() {
        return {

        }
    },
    computed: {
        group () {
            return this.groupsStore.currentItemOrNew
        },
        application () {
            return this.group.expert_panel;
        }
    },
    methods: {
        cancelDelete() {
            this.$router.go(-1);
        },
        async commitDelete() {
            this.groupsStore.delete(this.group.uuid);
            this.alertsStore.pushSuccess('Application deleted.');
            this.$router.push({name: 'ApplicationsIndex'});
        }
    }
}
</script>
<template>
  <div>
    <h2 class="block-title">
      Are you sure you want to delete this application?
    </h2>
    <p>
      You are about to delete the application for <strong class="underline">{{ application.name }}</strong>.
    </p>
    <p>This action cannot be undone.</p> 
    <p>Are you sure you want to continue?</p>

    <button-row 
      submit-text="Delete Application" 
      submit-variant="red" 
      @submitted="commitDelete" 
      @canceled="cancelDelete"
    />
  </div>
</template>