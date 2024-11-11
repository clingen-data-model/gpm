<script setup>
    import { computed, inject } from 'vue'
    import ReviewSection from '@/components/expert_panels/ReviewSection.vue'
    import ReviewMembership from '@/components/expert_panels/ReviewMembership.vue'
    import { formatDate } from '@/date_utils'

    const group = inject('group');
    const expertPanel = computed(() => group.value.expert_panel);
    const members = computed( () => {
        if (!group.value) {
            return [];
        }
        return group.value.members;
    });

    const basicInfo = computed(() => {
        return {
            type: group.value.type.name ? group.value.type.name.toUpperCase() : '',
            long_base_name: expertPanel.value.long_base_name,
            short_base_name: expertPanel.value.short_base_name,
        }
    });
</script>

<template>
    <div class="space-y-4">
        <ReviewSection title="Basic Information" name="basic-info">
            <object-dictionary :obj="basicInfo" label-class="w-40 font-bold" />
            <dictionary-row label="CDWG" label-class="w-40 font-bold">
                {{group.parent ?  group.parent.name : '--'}}
            </dictionary-row>
        </ReviewSection>

        <ReviewSection title="Membership" name="membership">
            <ReviewMembership />

            <div v-if="group.isVcep()" class="mt-6">
                <h4>Expertise of VCEP members</h4>
                <blockquote>
                    <markdown-block :markdown="expertPanel.membership_description" />
                </blockquote>
            </div>
        </ReviewSection>

        <ReviewSection title="Scope" name="scope">
            <h3>Genes</h3>
            <div class="mb-6">
                <p v-if="group.isGcep()">{{expertPanel.genes.map(g => g.gene_symbol).join(', ')}}</p>
                <simple-table
                    v-if="group.isVcep()"
                    :data="expertPanel.genes.map(g => ({id: g.id,gene: g.gene_symbol, disease: g.disease_name}))" :key-by="'id'"
                    :hide-columns="['id']"
                />
            </div>

            <h3>Description of scope</h3>
            <blockquote><markdown-block :markdown="expertPanel.scope_description" /></blockquote>
        </ReviewSection>

        <ReviewSection v-if="group.isGcep()" title="Plans" name="plans">
            <dictionary-row label="Selected protocol" label-class="w-48 font-bold">
                <div class="flex-none">
                    {{expertPanel.curation_review_protocol ? titleCase(expertPanel.curation_review_protocol.full_name) : null}}
                    <p v-if="expertPanel.curation_review_protocol_id == 100" class="mt-1">
                        <em>Details:</em> {{expertPanel.curation_review_protocol_other}}
                    </p>
                </div>
            </dictionary-row>
        </ReviewSection>

        <ReviewSection v-if="group.isGcep()" title="Attestations" name="attestations">
            <dictionary-row label="GCEP Attestation Signed" label-class="w-52 font-bold">
                {{formatDate(expertPanel.gcep_attestation_date)}}
            </dictionary-row>
            <dictionary-row label="GCI Training Date" label-class="w-52 font-bold">
                {{formatDate(expertPanel.gci_training_date)}}
            </dictionary-row>
            <dictionary-row label="NHGRI Attestation Signed" label-class="w-52 font-bold">
                {{formatDate(expertPanel.nhgri_attestation_date)}}
            </dictionary-row>
        </ReviewSection>

        <ReviewSection v-if="group.isVcep()" title="Attestations" name="attestations">
            <dictionary-row
                label="Reanalysis and Discrepancy Resolution Attestation Signed"
                label-class="w-52 font-bold"
            >
                {{formatDate(expertPanel.reanalysis_attestation_date)}}
            </dictionary-row>
            <dictionary-row label="NHGRI Attestation Signed" label-class="w-60 font-bold">
                {{formatDate(expertPanel.nhgri_attestation_date)}}
            </dictionary-row>
        </ReviewSection>
    </div>
</template>
