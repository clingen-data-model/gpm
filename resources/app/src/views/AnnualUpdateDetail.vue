<template>
    <div>
        <annual-update-form :uuid="annualReview.expert_panel.group.uuid" v-if="annualReview.expert_panel.group.uuid"/>
    </div>
</template>
<script>
import {api} from '@/http'
import AnnualUpdateForm from '@/views/AnnualUpdateForm'

export default {
    name: 'AnnualUpdateDetail',
    components: {
        AnnualUpdateForm,
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
            // formDef: formDef
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
        async getAnnualUpdate () {
            this.annualReview = await api.get(`/api/annual-updates/${this.id}`)
                .then(response => {
                    return response.data;
                });
        },
    },
    async mounted () {
        await this.getAnnualUpdate();
    }
}
</script>
