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
                    name: 'name',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'status',
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
            ]
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        hasSpecifications () {
            return this.group.expert_panel.specifications && this.group.expert_panel.specifications.length > 0;
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
        <div class="well mb-2" v-if="!hasSpecifications">
            No specifications on record in the CSPEC Registry.
        </div>
        <div v-else>
            <table class="mb-2">
                <thead>
                    <tr>
                        <th>Specification Name</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="spec in specifications" :key="spec.id">
                        <td>{{spec.name}}</td>
                        <td>{{spec.status}}</td>
                        <td>{{formatDate(spec.updated_at)}}</td>
                        <td>
                            <ul>
                                <!-- <li>
                                    <a target="cspec_view" :href="`https://cspec.genome.network/cspec/ui/svi/doc/${spec.cspec_id}`">View</a>
                                </li> -->
                                <li>
                                    <a target="cspec_edit" :href="`https://cspec.genome.network/cspec/ed/svi/doc/${spec.cspec_id}`">Edit</a>
                                </li>
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <a
            class="btn btn-lg blue"
            target="cspec-editor"
            :disabled="readonly"
            href="https://cspec.genome.network/"
        >
            Go to the CSpec Registry
            <icon-arrow-right class="inline" />
        </a>

        <note class="mt-2">It may take up to an hour for the latest updates in the CSPEC Registry to display here.</note>

    </div>
</template>
