<script>
import { api } from '@/http';
export default {
    name: 'AnnualUpdateAlert',
    props: {
        group: {
            type: Object,
            required: true,
        },
        showGroupName: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            annualReview: {},
            loading: false
        }
    },
    computed: {
        window () {
            return this.annualReview.window ? this.annualReview.window : {}
        },

        isPending() {
            return this.annualReview && !(this.annualReview.completed_at)
        },
        
        variant () {
            const now = new Date();
            if (!this.window.end) {
                return 'warning';
            }
            const endDate = new Date(Date.parse(this.window.end));

            return now > this.addDays(endDate, -7) ? 'danger' : 'warning';
        }
    },
    watch: {
        group: {
            immediate: true,
            handler () {
                this.getAnnualUpdate();
            }
        }
    },
    methods: {
        getAnnualUpdate () {
            if (!this.group.uuid) {
                return;
            }

            if (!this.group.expert_panel.definition_is_approved) {
                return;
            }

            this.loading = true;
            api.get(`/api/groups/${this.group.uuid}/expert-panel/annual-updates`, {headers: {'X-Ignore-Missing': 1} })
                .then(response => {
                    this.annualReview = response.data
                })
            this.loading = false;
        }
    }
}
</script>
<template>
  <static-alert v-if="isPending && annualReview.id" :variant="variant">
    <div class="flex space-x-2 items-center mb-3">
      <icon-review height="30" width="30" />
      <div>
        <p>
          <span v-if="showGroupName"><strong>{{ group.displayName }}</strong> has</span>
          <span v-else>You have</span>
          an <strong>annual update for {{ window.for_year }}</strong> due on <strong>{{ formatDate(window.end) }}</strong>.
        </p>
        <router-link 
          v-if="group.uuid" 
          class="btn font-bold"
          :to="{name: 'AnnualUpdate', params: {uuid: group.uuid}}"
        >
          Complete the Annual Update
        </router-link>
      </div>
    </div>
  </static-alert>
</template>