<script>
export default {
    name: 'SubgroupList',
    props: {

    },
    data() {
        return {

        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        showMemberReportButton () {
            return this.hasAnyPermission([['members-invite', this.group], 'groups-manage', 'ep-applications-manage', 'annual-updates-manage'])
        },
        exportUrl () {
            return `/api/report/groups/${this.group.uuid}/subgroup-member-export`;
        },
    }
}
</script>
<template>
  <div>
    <div class="flex justify-between">
      <h2 class="mb-4">
        Subgroups
      </h2>
      <dropdown-menu hide-cheveron orientation="right">
        <template #label>
          <button class="btn btn-icon">
            <icon-download width="16" height="16" />
          </button>
        </template>
        <dropdown-item class="text-right font-bold">
          Downloads:
        </dropdown-item>
        <!-- <dropdown-item class="text-right">
                    <a :href="`/api/report/groups/${group.uuid}/subgroups-coi-report`">COI Report</a>
                    <note class="inline"> (PDF)</note>
                </dropdown-item> -->
        <dropdown-item v-if="showMemberReportButton" class="text-right">
          <a :href="exportUrl">Member Export</a>
          <note class="inline">
            (CSV)
          </note>
        </dropdown-item>
      </dropdown-menu>
    </div>
    <ul>
      <li v-for="childGroup in group.children" :key="childGroup.id" class="child-group">
        <popover hover arrow placement="left" class="block w-full">
          <template #content>
            <div class="text-xs">
              <dictionary-row label="Status" label-class="font-bold" label-width="8em">
                {{ childGroup.status.name }}
              </dictionary-row>
              <dictionary-row
                v-if="childGroup.chairs.length > 0"
                label="Chairs"
                label-class="font-bold"
                label-width="8em"
                class="my-1"
              >
                {{ childGroup.chairs.map(c => c.person.name).join(', ') }}
              </dictionary-row>
              <dictionary-row
                v-if="childGroup.coordinators.length > 0"
                label="Coordinators"
                label-class="font-bold"
                label-width="8em"
              >
                {{ childGroup.coordinators.map(c => c.person.name).join(', ') }}
              </dictionary-row>
              <dictionary-row label="# Members" label-width="8em" label-class="font-bold">
                {{ childGroup.members_count }}
              </dictionary-row>
            </div>
          </template>
          <router-link :to="{name: 'GroupDetail', params: {uuid: childGroup.uuid}}" class="block w-full">
            {{ childGroup.name }}
          </router-link>
        </popover>
      </li>
    </ul>
  </div>
</template>
<style lang="postcss" scoped>
  .child-group {
    @apply px-2 border-t  border-l border-r hover:bg-blue-100 bg-white;
    padding-top: .5rem;
    padding-bottom: .5rem;
  }
  .child-group:first-child {
    @apply rounded-t-lg;
  }
  .child-group:last-child {
    @apply border-b rounded-b-lg;
  }
</style>
