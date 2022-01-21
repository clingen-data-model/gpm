<template>
    <div>
        <annual-review-form :uuid="annualReview.expert_panel.group.uuid" v-if="annualReview.expert_panel.group.uuid"/>

    </div>
</template>
<script>
import {api} from '@/http'
import AnnualReviewForm from '@/views/AnnualReviewForm'
import formDef from '@/forms/annual_review'
import DataForm from '@/components/forms/DataForm'

export default {
    name: 'AnnualReviewDetail',
    components: {
        AnnualReviewForm,
        DataForm
    },
    props: {
        id: {
            required: true,
            type: String
        }
    },
    data() {
        return {
            annualReview: {
                data: {},
                submitter: {},
                expert_panel: {
                    group: {
                        members: []
                    }
                },
                window: {}
            },
            errors: {},
            formDef: formDef
        }
    },
    computed: {
        expertPanel () {
            return this.annualReview.expert_panel || {}
        },
        window () {
            return this.annualReview.window || {}
        },
        submitter () {
            return this.submitter ? this.submitter.person : {}
        },
        
    },
    methods: {
        async getAnnualReview () {
            this.annualReview = await api.get(`/api/annual-reviews/${this.id}`)
                .then(response => {
                    return response.data;
                });
        },
    },
    async mounted () {
        await this.getAnnualReview();
    }
}
</script>