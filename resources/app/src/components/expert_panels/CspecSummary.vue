<template>
    <div>
        <!-- <pre>{{ {specifications: group.expert_panel.specifications} }}</pre> -->
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
                    <a :href="`https://cspec.genome.network/cspec/ui/svi/ruleset/${item.id}`" class="btn btn-xs">
                        Go to rules
                    </a>
                </template>
                <template v-slot:cell-status_name="{item}">
                    <badge :color="item.status.color">{{item.status.name}}</badge>
                </template>
            </data-table>
            <a 
                href="https://cspec.genome.network/"
                class="btn btn-xl blue"
                target="cspec"
            >
                Go to the CSpec Registry
                <icon-external-link class="inline"></icon-external-link>
            </a>
        </div>
    </div>
</template>
<script>
export default {
    name: 'Cspec Summary',
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
        group (to, from) {
            console.log({to, from})
            // if (!from || to.id != from.id) {
                this.$store.dispatch('groups/getSpecifications', to)
            // }
        }
    },
    mounted() {
        console.log('mounted');
        this.$store.dispatch('groups/getSpecifications', this.group)
    }
}
</script>