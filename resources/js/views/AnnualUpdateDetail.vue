<script>
import {api} from '@/http'
import AnnualUpdateForm from '@/views/AnnualUpdateForm.vue'

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
    async mounted () {
        await this.getAnnualUpdate();
    },
    methods: {
        async getAnnualUpdate () {
            // eslint-disable-next-line no-console
            console.log(`AnnualUpdateDetail.getAnnualUpdate: ${this.id}`)
            this.annualReview = await api.get(`/api/annual-updates/${this.id}`)
                .then(response => {
                    return response.data;
                });
        },
    }
}
</script>
<template>
    <div>
        <AnnualUpdateForm
            v-if="annualReview.expert_panel.group.uuid"
            :id="id"
            :uuid="annualReview.expert_panel.group.uuid"
        />
    </div>
</template>
