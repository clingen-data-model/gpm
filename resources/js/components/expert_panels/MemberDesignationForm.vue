<script>
import MemberDesignationRow from './MemberDesignationRow.vue'
export default {
    name: 'MemberDesignationForm',
    components: {
        MemberDesignationRow,
    },
    props: {
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    computed: {
        group: {
            get() {
                return this.$store.getters['groups/currentItemOrNew'];
            }
        },
        canEdit () {
            return this.hasAnyPermission([
                        'ep-applications-manage',
                        ['application-edit', this.group]
                    ])
                    && !this.readonly;
        }
    },
    watch: {
        group: {
            immediate: true,
            async handler (to, from) {
                if ((to.id && (!from || to.id !== from.id))) {
                    this.$store.dispatch('groups/getMembers', this.group);
                }
            }
        }
    },
    methods: {
        getMembers () {
            this.$store.dispatch('groups/getMembers', {group: this.group})
        },
        handleMemberUpdate () {
            this.$emit('updated');
        }
    },
}
</script>
<template>
  <div>
    <table class="text-sm">
      <thead>
        <tr>
          <th colspan="2">
&nbsp;
          </th>
          <th colspan="1" colgroup="biocurator">
&nbsp;
          </th>
          <th colspan="2" colgroup="biocurator" class="text-center">
            Training
          </th>
          <th colspan="2">
&nbsp;
          </th>
        </tr>
        <tr>
          <th>First Name</th>
          <th>Last Name</th>
          <th colgroup="biocurator" class="w-24">
            Biocurator
          </th>
          <th colgroup="biocurator" class="w-20">
            Level 1
          </th>
          <th colgroup="biocurator" class="w-20">
            Level 2
          </th>
          <th>Biocurator Trainer</th>
          <th>
            Core Approval Member
            <popover
              arrow
              content="Core approval members are responsible for ongoing final approval of variant classifications."
              hover
            >
              <icon-info class="inline-block cursor-pointer" :width="16" :height="16" />
            </popover>
          </th>
        </tr>
      </thead>
      <tbody>
        <MemberDesignationRow
          v-for="member in group.activeMembers"
          :key="member.id"
          :ref="`memberRow${member.id}`"
          :member="member"
          :readonly="readonly"
          @updated="handleMemberUpdate"
        />
      </tbody>
    </table>
    <!-- <div class="flex items-center mt-2 pt-2 border-t">
            <icon-info :width="14" :height="14" />
            &nbsp;
            <div>Core approval members are responsible for ongoing final approval of variant classifications.</div>
        </div> -->
  </div>
</template>
<style scoped>
    th {
        @apply bg-white border-0;
    }
    th[colgroup=biocurator],
    td[colgroup=biocurator]
    {
        @apply bg-blue-50;
    }
</style>
