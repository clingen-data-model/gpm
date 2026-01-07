<script>
import {ref, watch, computed, nextTick} from 'vue';
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

        const selectedGene = ref(null)
        const selectKey = ref(0)
        const adding = ref(false)

        const bulkCheckResults = ref([])
        const showPasteModal = ref(false)
        const pasteText = ref('')
        const pasteArea = ref(null)
        const reviewing = ref(false)


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
                await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, { genes: [ payloadItem ] })                
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
            if (!group.value.uuid) return;
            if (loading.value) return 
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

        function openPasteModal () {
            pasteText.value = ''
            bulkCheckResults.value = []
            availabilityMap.value = new Map()
            lastReviewedSymbols.value = []

            showPasteModal.value = true
            nextTick(() => pasteArea.value && pasteArea.value.focus())
        }

        function closePasteModal () {
            showPasteModal.value = false
            pasteText.value = ''
            bulkCheckResults.value = []

            availabilityMap.value = new Map()
            lastReviewedSymbols.value = []
        }

        const alreadyAddedSet = computed(() => {
            const arr = Array.isArray(geneCheckResults.value) ? geneCheckResults.value : []
            return new Set(arr.map(r => String(r?.gene_symbol ?? '').toUpperCase()).filter(Boolean))
        })

        const availabilityMap = ref(new Map())
        const lastReviewedSymbols = ref([])

        async function onReviewClick () {
            const genes = (pasteText.value || '').split(/[, \n\t]+/).map(s => s.trim()).filter(Boolean)

            if (genes.length === 0) {
                store.commit('pushError', 'Please paste at least one gene symbol separated by commas.')
                return
            }

            reviewing.value = true
            try {
                lastReviewedSymbols.value = [...new Set(genes.map(s => s.toUpperCase()))]
                const resp = await api.post('/api/genes/availability', { genes: lastReviewedSymbols.value.join(',') })
                const rows = Array.isArray(resp.data) ? resp.data : (resp.data?.data || [])

                const map = new Map()
                for (const row of rows) {
                    const sym = String(row.gene_symbol ?? '').toUpperCase()
                    if (sym) map.set(sym, row)
                }
                availabilityMap.value = map
                bulkCheckResults.value = rows
            } catch (e) {
                store.commit('pushError', 'Failed to review pasted genes.')
                console.error(e)
            } finally {
                reviewing.value = false
            }
        }

        const reviewBuckets = computed(() => {
            const already = []
            const notFound = []
            const ready = []

            const alreadySet = alreadyAddedSet.value

            for (const sym of lastReviewedSymbols.value) {
                const isAlready = alreadySet.has(sym)
                const existsInGT = availabilityMap.value.has(sym)

                if (isAlready) { already.push(sym) } else if (existsInGT) { ready.push(sym) } else { notFound.push(sym)}
            }

            return { ready, already, notFound }

        })

        const submittingBulk = ref(false)

        async function submitBulkFromReviewUI () {
            const symbols = reviewBuckets.value.ready
            if (!symbols.length) return
            
            const toAdd = symbols.map(sym => {
                const row = availabilityMap.value.get(sym) || {}
                return {
                    hgnc_id: row.hgnc_id ?? null,
                    gene_symbol: row.gene_symbol ?? sym
                }
            })


            const missing = toAdd.filter(g => !g.hgnc_id)
            const finalToAdd = toAdd.filter(g => g.hgnc_id)

            if (!finalToAdd.length) {
                store.commit('pushError', 'No addable genes were found (missing HGNC IDs).')
                return
            }
            if (missing.length) {
                store.commit('pushError', `Some genes are missing HGNC IDs and were skipped: ${missing.map(m => m.gene_symbol).join(', ')}`)
            }

            submittingBulk.value = true
            try {
                await api.post(`/api/groups/${group.value.uuid}/expert-panel/genes`, { genes: finalToAdd })
                store.commit('pushSuccess', `Added ${finalToAdd.length} gene${finalToAdd.length > 1 ? 's' : ''}.`)
                await getGenes()
                showPasteModal.value = false
                bulkCheckResults.value = []
                pasteText.value = ''
                availabilityMap.value = new Map()
                lastReviewedSymbols.value = []

            } catch (err) {
                if (is_validation_error(err)) {
                    const messages = err.response?.data?.errors || {}
                    const items = Object.keys(messages).map(k => `${k}: ${[].concat(messages[k]).join(', ')}`)
                    store.commit('pushError', items.join('\n') || 'Validation failed while adding genes.')
                } else {
                    store.commit('pushError', err?.response?.data?.message || 'Failed to add genes.')
                }
            } finally {
                submittingBulk.value = false
            }
        }

        watch(() => group.value?.uuid, (uuid, prev) => {
                if (!uuid) return
                if (uuid === prev && geneCheckResults.value?.length) return
                getGenes()
            }, { immediate: true }
        )

        const onChildChange = async () => {
            await getGenes()
            context.emit('geneschanged')
            context.emit('update')
        }

        return {
            group, genesAsText, loading, errors, resetErrors, hideForm, cancel,
            geneCheckResults, selectedGene, adding, addGene, onChildChange, selectKey,
            showPasteModal, pasteText, pasteArea, openPasteModal, closePasteModal, onReviewClick, reviewing, bulkCheckResults,
            reviewBuckets, submitBulkFromReviewUI, submittingBulk

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
        <div v-if="genesAsText != ''" class="mb-2">{{ genesAsText }}</div>
        <div v-else class="well cursor-pointer mb-2" @click="showForm">{{ loading ? `Loading...` : `No genes have been added to the gene list.` }}</div>

        <div v-if="editing && !readonly" class="border rounded bg-gray-50 p-4 mb-4">
            <label class="block text-sm font-semibold mb-2">Add gene</label>
            <div class="flex items-center gap-2">
                <GeneSearchSelect v-model="selectedGene" placeholder="Search gene by HGNC symbol…" class="w-full max-w-xl" :key="selectKey" />
                <button type="button" class="rounded bg-blue-600 text-white px-3 py-1.5 text-sm disabled:opacity-50" :disabled="!selectedGene || adding" @click="addGene">
                    {{ adding ? 'Adding…' : 'Add' }}
                </button>
                &nbsp; OR &nbsp; 
                <button type="button" class="rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-100" @click="openPasteModal">Bulk paste genes</button>
            </div>
        </div>

        <div v-if="geneCheckResults.length">
            <GeneCurationStatus :genes="geneCheckResults" :groupID="group.uuid" :editing="editing" :readonly="readonly" @removed="onChildChange" />
        </div>
    </div>

    <teleport to="body">
        <div
            v-if="showPasteModal"
            class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50"
            @click.self="closePasteModal"
        >
            <div class="w-[min(52rem,92vw)] bg-white rounded-lg shadow-xl">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h3 class="text-base font-semibold">Paste genes</h3>
                    <button class="text-xl leading-none px-2 py-1" aria-label="Close" @click="closePasteModal">×</button>
                </div>

                <div class="px-4 py-4">
                    <p class="text-sm text-gray-600 mb-2">
                        Enter gene symbols separated by commas or spaces.
                    </p>
                    <textarea ref="pasteArea" v-model="pasteText" class="w-full min-h-[260px] border rounded-md p-3 font-mono text-sm outline-none focus:ring focus:ring-blue-200" placeholder="BRCA1 BRCA2 CFTR"></textarea>
                </div>

                <div class="flex justify-end gap-2 border-t px-4 py-3">
                    <button class="rounded border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-100" @click="closePasteModal">Cancel</button>
                    <button class="rounded bg-blue-600 text-white px-3 py-1.5 text-sm disabled:opacity-50" :disabled="!pasteText.trim() || reviewing" @click="onReviewClick">{{ reviewing ? 'Reviewing…' : 'Review' }}</button>
                    <button class="rounded bg-green-600 text-white px-3 py-1.5 text-sm disabled:opacity-50" :disabled="reviewBuckets.ready.length === 0 || submittingBulk" @click="submitBulkFromReviewUI" title="Add only genes that exist in GT and aren’t already in this list">
                        {{ submittingBulk ? 'Adding…' : `Add ${reviewBuckets.ready.length} gene${reviewBuckets.ready.length !== 1 ? 's' : ''}` }}
                    </button>
                </div>


                <div v-if="lastReviewedSymbols.length" class="px-4 py-3">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm">
                        Ready to add <span class="font-semibold">{{ reviewBuckets.ready.length }}</span>
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm">
                        Already in list <span class="font-semibold">{{ reviewBuckets.already.length }}</span>
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-sm">
                        Not found in GT <span class="font-semibold">{{ reviewBuckets.notFound.length }}</span>
                        </span>
                    </div>
                    <div v-if="reviewBuckets.ready.length === 0 && reviewBuckets.already.length === 0" class="text-sm text-gray-600 mb-2">
                        No pasted genes were found in GeneTracker.
                    </div>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="font-semibold">Ready to add: </span>
                            <span v-if="!reviewBuckets.ready.length" class="text-gray-500"> — </span>
                            <span v-else>{{ reviewBuckets.ready.join(', ') }}</span>
                        </div>

                        <div>
                            <span class="font-semibold">Already in list: </span>
                            <span v-if="!reviewBuckets.already.length" class="text-gray-500"> — </span>
                            <span v-else>{{ reviewBuckets.already.join(', ') }}</span>
                        </div>

                        <div>
                            <span class="font-semibold">Not found in GT: </span>
                            <span v-if="!reviewBuckets.notFound.length" class="text-gray-500"> — </span>
                            <span v-else>{{ reviewBuckets.notFound.join(', ') }}</span>
                        </div>
                    </div>
                    <!-- <GeneCurationStatus :genes="bulkCheckResults" :groupID="group.uuid" :editing="false" :readonly="true" /> -->
                </div>
            </div>
        </div>
    </teleport>
</template>