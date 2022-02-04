<template>
    <static-alert :variant="variant"  v-if="isPending && annualReview.id">
        <div class="flex space-x-2 items-center mb-3">
            <icon-review height="30" width="30"></icon-review>
            <div>
                <p>
                    <span v-if="showGroupName"><strong>{{group.displayName}}</strong> has</span>
                    <span v-else>You have</span>
                    an <strong>annual review for {{window.for_year}}</strong> due on <strong>{{formatDate(window.end)}}</strong>.
                </p>
                <router-link 
                    class="btn font-bold" 
                    :to="{name: 'AnnualReview', params: {uuid: group.uuid}}"
                    v-if="group.uuid"
                >
                    Complete the Annual review
                </router-link>
            </div>
        </div>
    </static-alert>
</template>
<script>
import { api } from '@/http';
export default {
    name: 'AnnualReviewAlert',
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
            return !(this.annualReview.completed_at)
        },
        
        variant () {
            const now = new Date();
            if (!this.window.end) {
                console.log('no window.end');
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
                this.getAnnualReview();
            }
        }
    },
    methods: {
        async getAnnualReview () {
            if (!this.group.uuid) {
                return;
            }

            if (!this.group.expert_panel.definition_is_approved) {
                return;
            }

            this.loading = true;
            await api.get(`/api/groups/${this.group.uuid}/expert-panel/annual-reviews`)
                .then(response => {
                    this.annualReview = response.data
                });
            this.loading = false;
        }
    }
}
</script>