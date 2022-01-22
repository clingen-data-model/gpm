<template>
    <static-alert :variant="variant"  v-if="isPending">
        <p>
            You have an <strong>annual review for {{window.for_year}}</strong> due on <strong>{{formatDate(window.end)}}</strong>.
        </p>
        <router-link 
            class="btn font-bold" 
            :to="{name: 'AnnualReview', params: {uuid: group.uuid}}"
            v-if="group.uuid"
        >
            Complete the Annual review
        </router-link>
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
            this.loading = true;
            await api.get(`/api/groups/${this.group.uuid}/expert-panel/annual-reviews`)
                .then(response => {
                    this.annualReview = response.data
                });
            this.loading = false;
        }
    },
    mounted () {
        this.getAnnualReview();
    }
}
</script>