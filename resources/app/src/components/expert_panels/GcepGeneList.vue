<template>
    <div>
        <h4 class="flex justify-between mb-2">
            Gene List
            <edit-icon-button 
                v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
                @click="showForm"
            ></edit-icon-button>
        </h4>
        <div v-if="editing">
            <input-row 
                :vertical="true"
                label="List the gene symbols for the genes the Expert Panel plans to curate.  Separate genes by commas, spaces, or new lines."
                :errors="errors.genes"
            >
                <textarea id="" class="w-full" rows="5" v-model="genesAsText" placeholder="ABC, DEF1, BEAN"></textarea>
            </input-row>
        </div>
        <div v-else>
            <p v-if="genesAsText" style="text-indent: 1rem;">{{genesAsText}}</p>
            <div class="well cursor-pointer" v-else @click="showForm">
                {{ loading ? `Loading...` : `No genes have been added to the gene list.`}}
            </div>
        </div>
    </div>
</template>
<script>
import {ref, watch, computed, onMounted} from 'vue';
import {useStore} from 'vuex';
import formFactory from '@/forms/form_factory'
import is_validation_error from '@/http/is_validation_error'
import api from '@/http/api'
import { hasAnyPermission } from '@/auth_utils'
import Group from '@/domain/group'

export default {
    name: 'GcepGeneList',
    components: {
    },
    props: {
        editing: {
            type: Boolean,
            required: false,
            default: true
        }
    },
    emits: [
        'saved',
        'canceled',
        'update:editing'
    ],
    setup(props, context) {
        const store = useStore();

        const loading = ref(false);
        const genesAsText = ref(null);

        const {errors, resetErrors, submitForm} = formFactory(props, context)

        const group = computed({
            get() {
                return store.getters['groups/currentItem'] || new Group();
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
                await api.post(`/api/groups/${group.value.uuid}/application/genes`, {genes});
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
                        if (g == 'genes') {
                            if (geneIdx) {
                                return `Gene #${(parseInt(geneIdx)+1)}, "${genes[geneIdx]}" wasn't found in our records.  Please confirm it is currently an approved HGNC gene symbol.`
                            }
                            return messages[key];
                        }
                    });
                    errors.value = {
                        genes: geneMessages
                    }
                }
            }
        };

        const showForm = () => {
            if (hasAnyPermission(['ep-applications-manage', ['application-edit', group]])) {
                resetErrors();
                console.log('emitting update:editing')
                context.emit('update:editing', true);
            }
        }

        watch(() => store.getters['groups/currentItem'], (to, from) => {
            if (to.id && (!from || to.id != from.id)) {
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
            hideForm,
            showForm,
            cancel,
            syncGenesAsText,
            save,
        }
    },
    methods: {
    }
}
</script>