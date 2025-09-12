<script>
import {ref, watch, computed, nextTick, onMounted} from 'vue';
import {useStore} from 'vuex';
import formFactory from '@/forms/form_factory'
import is_validation_error from '@/http/is_validation_error'
import api from '@/http/api'
import GeneCurationStatus from './GeneCurationStatus.vue';
import GeneSearchSelect from '@/components/forms/GeneSearchSelect.vue'

export default {
    name: 'GcepGeneList',
    components: {
        GeneCurationStatus, GeneSearchSelect 
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
    emits: [
        'saved',
        'canceled',
        'update:editing',
        'geneschanged',
        'update'
    ],
    setup(props, context) {
        const store = useStore();

        const loading = ref(false);
        const genesAsText = ref(null);
        const geneCheckResults = ref([]);
        const activeTab = ref('published');

        const selectedGene = ref(null)
        const selectKey = ref(0)
        const adding = ref(false)


        const {errors, resetErrors} = formFactory(props, context)

        const group = computed({
            get() {
                return store.getters['groups/currentItemOrNew'];
            },
            set (value) {
                store.commit('groups/addItem', value)
            }
        });

        const addGene = async () => {
            if (!selectedGene.value) return
            adding.value = true
            try {
                const payloadItem = {
                    hgnc_id: selectedGene.value.hgnc_id,
                    gene_symbol: selectedGene.value.gene_symbol ?? selectedGene.value.symbol
                }
                await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, { gene: payloadItem })                
                await getGenes()
                context.emit('saved')
                store.commit('pushSuccess', `Added ${payloadItem.gene_symbol}`)
            } catch (error) {
                const res = error?.response
                if (res?.status === 422 && res.data?.errors) {
                    const all = Object.values(res.data.errors).flat()
                    store.commit('pushError', all.join('\n'))
                } else {
                    store.commit('pushError', res?.data?.message || `Failed to add ${payloadItem.gene_symbol}`)
                }
            } finally {
                selectedGene.value = null
                await nextTick()
                selectKey.value++
                adding.value = false
            }
        }


        const getGenes = async () => {
            if (!group.value.uuid) {
                return;
            }
            loading.value = true;
            try {
                await store.dispatch('groups/getGenes', group.value);
                genesAsText.value = group.value.expert_panel.genes.map(g => g.gene_symbol).join(", ");
                geneCheckResults.value = group.value.expert_panel.genes;
            } catch (error) {
                let message = 'An unexpected error occurred.';
    
                if (error.response) {
                    if (typeof error.response.data === 'string') {
                        message = error.response.data;
                    } else if (error.response.data?.message) {
                        message = error.response.data.message;
                    } else if (Array.isArray(error.response.data?.errors)) {
                        message = error.response.data.errors.join(', ');
                    } else if (typeof error.response.data === 'object') {
                        message = JSON.stringify(error.response.data);
                    }
                } else if (error.message) {
                    message = error.message;
                }

                store.commit('pushError', message);
            }
            loading.value = false;
            
        }
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
        
        const save = async () => {
            const genes = genesAsText.value 
                            ? genesAsText.value
                                .split(/[, \n]/)
                                .filter(i => i !== '')
                            : [];

            try {
                await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, {genes});                
                hideForm();
                context.emit('saved')
                await getGenes();
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

        watch(() => store.getters['groups/currentItem'], (to, from) => {
            if (to.id && (!from || to.id !== from.id)) {
                // syncGenesAsText();
                getGenes();
            }
        })

        const onChildChange = async () => {
            await getGenes()
            context.emit('geneschanged')
            context.emit('update')
}
        onMounted(() => {
            getGenes();
        })

        return {
            group, genesAsText, loading, errors, resetErrors, hideForm, cancel, syncGenesAsText,
            save, geneCheckResults, activeTab, selectedGene, adding, addGene, onChildChange, selectKey 
        }
    },
    computed: {
        canEdit () {
            return this.hasAnyPermission(['groups-manage', ['application-edit', this.group]]);
        }
    },
    methods: {
        showForm () {
            if (this.canEdit) {
                this.resetErrors();
                this.$emit('update:editing', true);
            }
        }

    }
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
        <div v-if="editing && !readonly" class="border rounded bg-gray-50 p-4 mb-4">
            <label class="block text-sm font-semibold mb-2">Add gene</label>
            <div class="flex items-center gap-2">
                <GeneSearchSelect v-model="selectedGene" placeholder="Search gene by HGNC symbol…" class="w-full max-w-xl" :key="selectKey" />
                <button type="button" class="rounded bg-blue-600 text-white px-3 py-1.5 text-sm disabled:opacity-50" :disabled="!selectedGene || adding" @click="addGene">
                    {{ adding ? 'Adding…' : 'Add' }}
                </button>
            </div>
        </div>

            <!-- View mode stays the same -->
        <div v-else>
            <p v-if="genesAsText" class="pl-4">{{ genesAsText }}</p>
            <div v-else class="well cursor-pointer" @click="showForm">
                {{ loading ? `Loading...` : `No genes have been added to the gene list.` }}
            </div>
        </div>
        <div v-if="geneCheckResults.length">
            <GeneCurationStatus :genes="geneCheckResults" :groupID="group.uuid" :editing="editing" :readonly="readonly" @removed="onChildChange" />
        </div>
    </div>
</template>