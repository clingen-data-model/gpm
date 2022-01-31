<template>
    <div>
        <h1>Applicaiton for {{group.displayName}}</h1>
        <section>
            <h2>Basic Information</h2>
            <object-dictionary :obj="basicInfo" labelWidthClass="w-40" />
        </section>

        <section>
            <h2>Membership</h2>
            <simple-table :data="members" key-by="id" />

            <div v-if="isVcep">
                <h4>Expertise of VCEP members</h4>
                <markdown-block :markdown="expertPanel.membership_description" />
            </div>
        </section>

        <section>
            <h2>Scope of Work</h2>
            <h4>Genes</h4>
            <p>{{expertPanel.genes.map(g => g.gene.gene_symbol).join(', ')}}</p>
            <!-- <simple-table v-if="isVcep" :data="expertPanel.genes.map(g => ({id: g.id,gene: g.gene.gene_symbol, disease: g.disease.name}))" :key-by="'id'" :hide-columns="['id']" /> -->

            <h4>Description of scope</h4>
            <markdown-block :markdown="expertPanel.scope_description" />
        </section>

        <section v-if="isGcep">
            <h2>Attestations</h2>
            <dictionary-row label="GCEP Attestation Signed">
                {{formatDate(expertPanel.gcep_attestation_date)}}
            </dictionary-row>
            <dictionary-row label="GCI Training Date">
                {{formatDate(expertPanel.gci_training_date)}}
            </dictionary-row>
            <dictionary-row label="NHGRI Attestation Signed">
                {{formatDate(expertPanel.gci_training_date)}}
            </dictionary-row>
        </section>
        <!-- <section v-if="isGcep">
            <h2>Plans for Ongoing Review and Descrepency Resolution</h2>
            <p>{{expertPanel.curation_review_protocol.full_name}}</p>
            <p v-if="expertPanel.curation_review_protocol_id == 100">
                {{expertPanel.curation_review_protocol_other}}
            </p>
        </section> -->

        <section v-if="isVcep">

        </section>
    </div>
</template>
<script>
export default {
    name: 'ComponentName',
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        expertPanel () {
            return this.group.expert_panel;
        },
        isGcep () {
            return this.group.isGcep();
        },
        isVcep () {
            return this.group.isVcep();
        },
        basicInfo () {
            return {
                type: this.group.type.name,
                long_base_name: this.expertPanel.long_base_name,
                short_base_name: this.expertPanel.short_base_name,
                CDWG: this.group.parent.name,
            }
        },
        members () {
            return this.group.members.map(m => ({
                id: m.id,
                name: m.person.name,
                institution: m.person.institution ? m.person.institution.name : null,
                credentials: m.person.credentials,
                expertise: m.expertise,
                roles: m.roles.map(r => r.name).join(', '),
                coi_completed: this.formatDate(m.coi_last_completed)
            }));
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler: async function (to) {
                await this.$store.dispatch('groups/findAndSetCurrent', to);
                this.$store.dispatch('groups/getMembers', this.group);
            }
        }
    },
    methods: {

    }
}
</script>
<style scoped lang="postcss">
    section {
        @apply mt-8;
        max-width: 800px;
    }
    section:first-child {
        @apply mt-0
    }
    h2 {
        @apply mb-2
    }
    h4 {
        @apply mb-1
    }
</style>