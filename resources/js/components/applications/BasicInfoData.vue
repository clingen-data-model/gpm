<script>
import GroupForm from '@/components/groups/GroupForm.vue';

export default {
    name: 'BasicInfoData',
    components: {
        GroupForm,
    },
    data () {
        return {
            showInfoEdit: false,
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        },
        application () {
            return this.group.expert_panel;
        },
    },
    methods: {

    }
}
</script>
<template>
  <div>
    <div class="flex justify-between border-b pb-2 min-w-fit">
      <h3>Base Information</h3>
      <button class="btn btn-xs" @click="showInfoEdit = true">
        Edit
      </button>
    </div>
    <dictionary-row label-class="font-bold w-40" label="Long Base Name">
      {{ application.long_base_name || '--' }}
    </dictionary-row>
    <dictionary-row label-class="font-bold w-40" label="Short Base Name">
      {{ application.short_base_name || '--' }}
    </dictionary-row>
    <dictionary-row label-class="font-bold w-40" label="Affiliation ID">
      {{ application.affiliation_id || '--' }}
    </dictionary-row>
    <dictionary-row label-class="font-bold w-40" label="CDWG">
      {{ group.parent ? group.parent.name : null || '--' }}
    </dictionary-row>
    <dictionary-row label-class="font-bold w-40" label="Chair(s)">
      <span v-for="c,idx in group.chairs" :key="c.id">
        <router-link :to="{name: 'PersonDetail', params: {id: c.id}}">{{ c.person.name }}</router-link>
        <span v-if="idx < group.chairs.length - 1">, </span>
      </span>
    </dictionary-row>
    <dictionary-row label-class="font-bold w-40" label="Coordinator(s)">
      <span v-for="c,idx in group.coordinators" :key="c.id">
        <router-link :to="{name: 'PersonDetail', params: {id: c.id}}">{{ c.person.name }}</router-link>
        <span v-if="idx < group.coordinators.length - 1">, </span>
      </span>
    </dictionary-row>
    <dictionary-row label-class="font-bold w-40" label="Website URL">
      <a 
        v-if="application.stepIsApproved(1)" 
        :href="`https://clinicalgenome.org/affiliation/${application.affiliation_id}`"
        target="ep-website"
      >
        https://clinicalgenome.org/affiliation/{{ application.affiliation_id }}
      </a>
      <span v-else>https://clinicalgenome.org/affiliation/{{ application.affiliation_id || '--' }}</span>
    </dictionary-row>

    <teleport to="body">
      <modal-dialog v-model="showInfoEdit" title="Edit Group Info" size="md" @closed="showInfoEdit = false">
        <submission-wrapper @submitted="$refs.infoForm.save()" @canceled="$refs.infoForm.cancel()">
          <GroupForm 
            ref="infoForm"
            @canceled="showInfoEdit = false"
            @saved="showInfoEdit = false"
          />
        </submission-wrapper>
      </modal-dialog>
    </teleport>
  </div>
</template>