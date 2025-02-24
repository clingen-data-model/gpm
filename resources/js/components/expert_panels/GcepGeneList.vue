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
            ></edit-icon-button>
        </h4>
        <div v-if="editing">
            <input-row 
                v-model="genesAsText"
                type="large-text"
                label="List the gene symbols for the genes the Expert Panel plans to curate.  Separate genes by commas, spaces, or new lines."
                :errors="errors.genes"
                @update:modelValue="$emit('geneschanged'); $emit('update')"
                placeholder="ABC, DEF1, BEAN"
                vertical
            />
        </div>
        <div v-else>
            <p v-if="genesAsText" style="text-indent: 1rem;">{{genesAsText}}</p>
            <div class="well cursor-pointer" v-else @click="showForm">
                {{ loading ? `Loading...` : `No genes have been added to the gene list.`}}
            </div>
        </div>
    </div>
</template>