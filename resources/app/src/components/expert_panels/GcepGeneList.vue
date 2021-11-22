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
            <button-row @submit="save" @cancel="cancel" submit-text="Save"></button-row>
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
import {ref, watch, onMounted} from 'vue';
import {useStore} from 'vuex';
import formFactory from '@/forms/form_factory'
import is_validation_error from '@/http/is_validation_error'
import api from '@/http/api'
import { hasAnyPermission } from '@/auth_utils'

export default {
    name: 'GcepGeneList',
    components: {
    },
    props: {
        group: {
            required: true,
            type: Object
        }
    },
    emits: [
        'saved',
        'canceled',
        'editing'
    ],
    setup(props, context) {
        const store = useStore();

        const {errors, editing, hideForm, showForm: baseShowForm, cancel: baseCancel} = formFactory(props, context)
        const cancel = () => {
            getGenes();
            baseCancel();
        }
        const loading = ref(false);
        const genesAsText = ref(null);
        const syncGenesAsText = () => {
            genesAsText.value = props.group.expert_panel.genes 
                    ? props.group.expertPanel.genes.join(', ')
                    : null
            
            console.log({genesAsText: genesAsText.value});
        };
        const getGenes = async () => {
            loading.value = true;
            try {
                const genes = await api.get(`/api/groups/${props.group.uuid}/application/genes`)
                                .then(response => response.data);

                genesAsText.value = genes.map(g => g.gene_symbol).join(", ");
            } catch (error) {
                console.log(error);
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
                await api.post(`/api/groups/${props.group.uuid}/application/genes`, {genes});
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
            if (hasAnyPermission(['ep-applications-manage', ['application-edit', props.group]])) {
                baseShowForm();
            }
        }

        onMounted(() => {
            syncGenesAsText();
            getGenes();
        })

        watch(() => props.group.expert_panel.genes, () => {
            syncGenesAsText();
        })

        return {
            genesAsText,
            loading,
            errors,
            editing,
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