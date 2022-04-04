<template>
    <div class="application-review bg-gray-100 p-2">
        <section>
            <h2>Basic Information</h2>
            <object-dictionary :obj="basicInfo" label-class="w-40 font-bold" />
            <dictionary-row label="CDWG" label-class="w-40 font-bold">{{this.group.parent ?  this.group.parent.name : '--'}}</dictionary-row>
        </section>

        <section>
            <h2>Membership</h2>
            <simple-table :data="members" key-by="id" class="print:text-xs text-sm" />

            <div v-if="isVcep" class="mt-6">
                <h4>Expertise of VCEP members</h4>
                <blockquote>
                    <markdown-block :markdown="expertPanel.membership_description" />
                </blockquote>
            </div>
        </section>

        <section>
            <h2>Scope of Work</h2>
            <h3>Genes</h3>
            <div class="mb-6">
                <p v-if="isGcep">{{expertPanel.genes.map(g => g.gene_symbol).join(', ')}}</p>
                <simple-table 
                    v-if="isVcep" 
                    :data="expertPanel.genes.map(g => ({id: g.id,gene: g.gene_symbol, disease: g.disease_name}))" :key-by="'id'" 
                    :hide-columns="['id']" 
                />
            </div>

            <h3>Description of scope</h3>
            <blockquote><markdown-block :markdown="expertPanel.scope_description" /></blockquote>
        </section>

        <section v-if="isGcep">
            <h2>Plans for Ongoing Review and Descrepency Resolution</h2>
            <dictionary-row label="Selected protocol" label-class="w-48 font-bold">
                <div class="flex-none">
                    {{expertPanel.curation_review_protocol ? titleCase(expertPanel.curation_review_protocol.full_name) : null}}
                <p v-if="expertPanel.curation_review_protocol_id == 100" class="mt-1">
                    <em>Details:</em> {{expertPanel.curation_review_protocol_other}}
                </p>
                </div>
            </dictionary-row>
        </section>
        <section v-if="isGcep">
            <h2>Attestations</h2>
            <dictionary-row label="GCEP Attestation Signed" label-class="w-52 font-bold">
                {{formatDate(expertPanel.gcep_attestation_date)}}
            </dictionary-row>
            <dictionary-row label="GCI Training Date" label-class="w-52 font-bold">
                {{formatDate(expertPanel.gci_training_date)}}
            </dictionary-row>
            <dictionary-row label="NHGRI Attestation Signed" label-class="w-52 font-bold">
                {{formatDate(expertPanel.nhgri_attestation_date)}}
            </dictionary-row>
        </section>

        <div v-if="isVcep">
            <section>
                <h2>Attestations</h2>

                <dictionary-row 
                    label="Reanalysis and Descrepency Resolution Attestation Signed" 
                    label-class="w-52 font-bold"
                >
                    {{formatDate(expertPanel.reanalysis_attestation_date)}}
                </dictionary-row>
                <dictionary-row label="NHGRI Attestation Signed" label-class="w-60 font-bold">
                    {{formatDate(expertPanel.nhgri_attestation_date)}}
                </dictionary-row>
            </section>
        </div>
    </div>
</template>
<script>
import ApplicationStepReview from '@/components/expert_panels/ApplicationStepReview.vue'
export default {
    name: 'DefinitionReview',
    extends: ApplicationStepReview,
    computed: {
        basicInfo () {
            return {
                type: this.group.type.name ? this.group.type.name.toUpperCase() : '',
                long_base_name: this.expertPanel.long_base_name,
                short_base_name: this.expertPanel.short_base_name,
            }
        },
    },
    watch: {
        group: {
            immediate: true,
            handler (to, from) { 
                if ((to.id && (!from || to.id != from.id))) {
                    this.$store.dispatch('groups/getMembers', this.group);
                    this.$store.dispatch('groups/getGenes', this.group);
                }
            }
        }
    }
}
</script>