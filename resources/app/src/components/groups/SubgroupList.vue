<template>
    <div>
        <div class="flex justify-between">
            <h2 class="mb-4">Subgroups</h2>
            <dropdown-menu hide-cheveron orientation="right">
                <template v-slot:label>
                    <button class="btn btn-icon"><icon-download width="16" height="16" /></button>
                </template>
                <dropdown-item class="text-right font-bold">Downloads:</dropdown-item>
                <!-- <dropdown-item class="text-right">
                    <a :href="`/api/report/groups/${group.uuid}/subgroups-coi-report`">COI Report</a>
                    <note class="inline"> (PDF)</note>
                </dropdown-item> -->
                <dropdown-item  class="text-right" v-if="showMemberReportButton">
                    <a :href="exportUrl">Member Export</a>
                    <note class="inline"> (CSV)</note>
                </dropdown-item>
            </dropdown-menu>
        </div>
        <ul>
          <li class="child-group" v-for="group in group.children" :key="group.id">
            <popover hover arrow placement="left" class="block w-full">
              <template v-slot:content>
                <div class="text-xs">
                  <dictionary-row label="Status" label-class="font-bold" label-width="8em">{{group.status.name}}</dictionary-row>
                  <dictionary-row v-if="group.chairs.length > 0"
                      label="Chairs"
                      label-class="font-bold"
                      label-width="8em"
                      class="my-1"
                  >
                    {{group.coordinators.map(c => c.person.name).join(', ')}}
                  </dictionary-row>
                  <dictionary-row v-if="group.coordinators.length > 0"
                      label="Coordinators"
                      label-class="font-bold"
                      label-width="8em"
                  >
                    {{group.coordinators.map(c => c.person.name).join(', ')}}
                  </dictionary-row>
                  <dictionary-row label="# Members" label-width="8em" label-class="font-bold">
                    {{group.members_count}}
                  </dictionary-row>
                </div>
              </template>
              <router-link :to="{name: 'GroupDetail', params: {uuid: group.uuid}}" class="block w-full">
                {{group.name}}
              </router-link>
            </popover>
          </li>
        </ul>
    </div>
</template>
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
<style lang="postcss" scoped>
  .child-group {
    @apply px-2 border-t  border-l border-r hover:bg-blue-100 bg-white;
    padding-top: .5rem;
    padding-bottom: .5rem;
  }
  .child-group:first-child {
    @apply rounded-t-lg
  }
  .child-group:last-child {
    @apply border-b rounded-b-lg;
  }
</style>
