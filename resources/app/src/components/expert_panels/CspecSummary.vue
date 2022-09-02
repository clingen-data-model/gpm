<script>
export default {
    name: 'CspecSummary',
    props: {
        readonly: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            sort: {
                field: 'id',
                desc: false,
            },
            specFields: [
                {
                    name: 'gene_symbol',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'disease_name',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'status.name',
                    label: 'Status',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'updated_at',
                    label: 'Last Updated',
                    type: Date,
                    sortable: true,
                },
                {
                    name: 'id',
                    label: '',
                    type: Number,
                    sortable: false,
                }
            ]
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        hasSpecifications () {
            return true;
        },
        specifications () {
            return this.group.expert_panel.specifications;
        }
    },
    watch: {
        group (to) {
            this.$store.dispatch('groups/getSpecifications', to)
        }
    },
    methods: {
        goToCspec () {
            window.location = 'https://cspec.genome.network/'
        }
    },
    mounted() {
        this.$store.dispatch('groups/getSpecifications', this.group)
    }
}
</script>
<template>
    <div>
        <div class="well" v-if="!hasSpecifications">
            No specifications on record.
        </div>
        <div v-else>
            <data-table
                :fields="specFields"
                :data="group.expert_panel.specifications"
                v-model:sort="sort"
                class="mb-6"
            >
                <template v-slot:cell-id="{item}">
                    <a :href="`https://cspec.genome.network/cspec/ui/svi/ruleset/${item.id}`" target="cspec" class="btn btn-xs">
                        Go to rules
                    </a>
                </template>
                <template v-slot:cell-status_name="{item}">
                    <badge :color="item.status.color">{{item.status.name}}</badge>
                </template>
            </data-table>
        </div>
        <button
            @click="goToCspec"
            class="btn btn-xl blue"
            target="cspec"
            :disabled="readonly"
        >
            Go to the CSpec Registry
            <icon-external-link class="inline"></icon-external-link>
        </button>
            <icon-external-link class="inline"></icon-external-link>
    </div>
</template>
