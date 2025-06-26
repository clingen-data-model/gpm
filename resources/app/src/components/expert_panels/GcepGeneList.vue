
<script>
import {ref, watch, computed, onMounted} from 'vue';
import {useStore} from 'vuex';
import formFactory from '@/forms/form_factory'
import is_validation_error from '@/http/is_validation_error'
import api from '@/http/api'

export default {
    name: 'GcepGeneList',
    components: {
    },
    props: {
        editing: {
            type: Boolean,
            required: false,
            default: true
        },
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    computed: {
        canEdit () {
            return this.hasAnyPermission(['groups-manage', ['application-edit', this.group]]);
        }
    },    
    emits: [
        'saved',
        'canceled',
        'update:editing',
        'geneschanged',
        'update'
    ],
    methods: {
        showForm () {
            if (this.canEdit) {
                this.resetErrors();
                this.$emit('update:editing', true);
            }
        }        
    },
    setup(props, context) {
        const store = useStore();
        const loading = ref(false);
        const genesAsText = ref(null);
        const {errors, resetErrors} = formFactory(props, context)
        const group = computed({
            get() {
                return store.getters['groups/currentItemOrNew'];
            },
            set (value) {
                store.commit('groups/addItem', value)
            }
        });
        
        const hideForm = () => {
            context.emit('update:editing', false);
            errors.value = {};
        }
        const cancel = () => {
            resetErrors();
            getGenes();
            hideForm();
            context.emit('canceled');
        }

        const syncGenesAsText = () => {
            if (!group.value.expert_panel) {
                return;
            }
            genesAsText.value = group.value.expert_panel.genes
                ? group.value.expertPanel.genes.join(', ')
                : null
        };
        const getGenes = async () => {
            if (!group.value.uuid) {
                return;
            }
            loading.value = true;
            try {
                await store.dispatch('groups/getGenes', group.value);
                genesAsText.value = group.value.expert_panel.genes.map(g => g.gene_symbol).join(", ");
            } catch (error) {
                store.commit('pushError', error.response.data);
            }
            loading.value = false;
            
        }
        
        const save = async () => {
            const genes = genesAsText.value 
                            ? genesAsText.value
                                .split(new RegExp(/[, \n]/))
                                .filter(i => i !== '')
                            : [];
                            
            try {
                await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, {genes});
                hideForm();
                context.emit('saved')
                getGenes();
            } catch (error) {
                if (is_validation_error(error)) {
                    const messages = error.response.data.errors
                    if (messages.group) {
                        messages.group
                            .forEach(m => {
                                store.pushError(m)
                            })
                    }
                    const geneMessages = Object.keys(messages).map(key => {
                        const [g, geneIdx] = key.split('.')
                        if (g === 'genes') {
                            if (geneIdx) {
                                return `Gene #${(Number.parseInt(geneIdx)+1)}, "${genes[geneIdx]}" wasn't found in our records.  Please confirm it is currently an approved HGNC gene symbol.`
                            }
                            return messages[key];
                        }
                        return `Gene validation error: ${key}`;
                    });
                    errors.value = {
                        genes: geneMessages
                    }
                }
            }
        };
        const geneCheckResults = ref({
            published: [],
            notPublished: [],
            notCurated: []
        });
        const checkGeneStatusLoading = ref(false);
        const activeTab = ref('published');
        const checkGenesStatus = async () => {
            const genes = genesAsText.value
                ? genesAsText.value.split(/[, \n]/).filter(i => i.trim() !== '')
                : [];
            if (genes.length === 0) return;
            checkGeneStatusLoading.value = true;
            geneCheckResults.value = {
                published: [],
                notPublished: [],
                notCurated: [],
            };
            try {
                const response = await api.post('/api/genes/check-genes', {
                    gene_symbol: genes.join(', '),
                });
                const data = response.data.data;
                // Group all results by status
                const grouped = {
                    published: [],
                    notPublished: [],
                    notCurated: [],
                    notFound: [],
                };
                // Find all input genes with responses
                const foundGeneSymbols = data.map(c => c.gene_symbol);
                genes.forEach(symbol => {
                    const matches = data.filter(item => item.gene_symbol === symbol);
                    if (matches.length === 0) {
                        grouped.notCurated.push({ gene_symbol: symbol });
                    } else {
                        matches.forEach(match => {
                            if (match.current_status === 'Published') {
                                grouped.published.push(match);
                            } else {
                                grouped.notPublished.push(match);
                            }
                        });
                    }
                });
                geneCheckResults.value = grouped;
            } catch (e) {
                store.commit('pushError', 'Failed to check gene status.');
            } finally {
                checkGeneStatusLoading.value = false;
            }
        };
        watch(() => store.getters['groups/currentItem'], (to, from) => {
            if (to.id && (!from || to.id !== from.id)) {
                // syncGenesAsText();
                getGenes();
            }
        })
        onMounted(() => {
            getGenes();
        })
        watch(() => group.value.expert_panel.genes, () => {
            // syncGenesAsText();
        })
        return {
            group,
            genesAsText,
            loading,
            errors,
            resetErrors,
            hideForm,
            cancel,
            syncGenesAsText,
            save,
            geneCheckResults,
            checkGeneStatusLoading,
            checkGenesStatus,
            activeTab,
        }
    },
}
</script>
<template>
    <div>
        <h4 class="flex justify-between mb-2">
            Gene List
            <edit-icon-button 
                v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing && !readonly"
                @click="showForm"
            />
        </h4>
        <div v-if="editing">
            <input-row 
                v-model="genesAsText"
                type="large-text"
                label="List the gene symbols for the genes the Expert Panel plans to curate.  Separate genes by commas, spaces, or new lines."
                :errors="errors.genes"
                placeholder="ABC, DEF1, BEAN"
                vertical
                @update:model-value="$emit('geneschanged'); $emit('update')"
            />
            
            <div class="mt-4">
                <button @click="checkGenesStatus" class="btn btn-sm btn-primary">
                    {{ checkGeneStatusLoading ? 'Checking...' : 'Check Gene Status' }}
                </button>
            </div>
            <div v-if="geneCheckResults.published.length || geneCheckResults.notPublished.length || geneCheckResults.notCurated.length">
                <div class="flex space-x-2 border-b border-gray-300 mb-2">
                    <button
                    class="px-4 py-2"
                    :class="activeTab === 'published' ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-600'"
                    @click="activeTab = 'published'"
                    >
                    Published ({{ geneCheckResults.published.length }})
                    </button>
                    <button
                    class="px-4 py-2"
                    :class="activeTab === 'notPublished' ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-600'"
                    @click="activeTab = 'notPublished'"
                    >
                    Not Published ({{ geneCheckResults.notPublished.length }})
                    </button>
                    <button
                    class="px-4 py-2"
                    :class="activeTab === 'notCurated' ? 'border-b-2 border-blue-500 font-semibold' : 'text-gray-600'"
                    @click="activeTab = 'notCurated'"
                    >
                    Not Curated ({{ geneCheckResults.notCurated.length }})
                    </button>
                </div>
                <div class="mt-4">
                    <div v-if="activeTab === 'published'">
                        <div class="overflow-x-auto mt-4">
                            <table class="min-w-full table-auto border border-gray-300 rounded-md text-sm">
                                <thead class="bg-gray-100 text-left">
                                <tr>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Gene</th>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Expert Panel</th>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Status</th>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Status Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in geneCheckResults.published" :key="index" class="hover:bg-gray-50 border-t">
                                    <td class="px-4 py-2 font-mono font-medium text-blue-800">{{ item.gene_symbol }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ item.expert_panel || 'Unknown Panel' }}</td>
                                    <td class="px-4 py-2 text-green-700 font-semibold">{{ item.current_status || 'N/A' }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ item.current_status_date }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-if="activeTab === 'notPublished'">
                        <div class="overflow-x-auto mt-4">
                            <table class="min-w-full table-auto border border-gray-300 rounded-md text-sm">
                                <thead class="bg-gray-100 text-left">
                                <tr>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Gene</th>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Expert Panel</th>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Status</th>
                                    <th class="px-4 py-2 font-semibold text-gray-700">Status Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in geneCheckResults.notPublished" :key="index" class="hover:bg-gray-50 border-t">
                                    <td class="px-4 py-2 font-mono font-medium text-blue-800">{{ item.gene_symbol }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ item.expert_panel || 'Unknown Panel' }}</td>
                                    <td class="px-4 py-2 text-green-700 font-semibold">{{ item.current_status || 'N/A' }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ item.current_status_date }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-if="activeTab === 'notCurated'">
                        <div class="flex flex-wrap gap-2 mt-4">
                            <span v-for="(item, index) in geneCheckResults.notCurated" :key="index"
                                class="inline-block bg-gray-100 text-sm px-3 py-1 rounded font-mono"
                            > {{ item.gene_symbol }}</span>
                        </div>
                    </div>
                </div>
                </div>

        </div>
        <div v-else>
            <p v-if="genesAsText" style="text-indent: 1rem;">
                {{ genesAsText }}
            </p>
            <div v-else class="well cursor-pointer" @click="showForm">
                {{ loading ? `Loading...` : `No genes have been added to the gene list.` }}
            </div>
        </div>
    </div>
</template>
