<template>
    <div>
        <h4 class="flex justify-between mb-4">
            Gene List
            <edit-button 
                v-if="hasAnyPermission(['groups-manage'], ['edit-info', group]) && !editing"
                @click="showForm"
            ></edit-button>
        </h4>
        <div v-if="editing">
            <input-row 
                :vertical="true"
                label="List the gene symbols for the genes the Expert Panel plans to curate.  Separate genes by commas, spaces, or new lines.">
                <textarea id="" class="w-full" rows="5" v-model="genesAsText" placeholder="ABC, DEF1, BEAN"></textarea>
            </input-row>
            <button-row @submit="save" @cancel="cancel" submit-text="Save"></button-row>
        </div>
        <div v-else>
            <p v-if="genesAsText">{{genesAsText}}</p>
            <div class="well" v-else>No genes have been added to the gene list.</div>
        </div>
    </div>
</template>
<script>
import {ref, watch, onMounted} from 'vue';
import {useStore} from 'vuex';
import EditButton from '@/components/buttons/EditIconButton'
import formFactory from '@/forms/form_factory'
import is_validation_error from '@/http/is_validation_error'
import api from '@/http/api'

export default {
    name: 'GcepGeneList',
    components: {
        EditButton
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
        const {errors, editing, hideForm, showForm, cancel} = formFactory(props, context)
        const genesAsText = ref(null);
        const syncGenesAsText = () => {
            genesAsText.value = props.group.expert_panel.genes 
                    ? props.group.expertPanel.genes.join(', ')
                    : null
        };
        const getGenes = async () => {
            try {
                const genes = await api.get(`/api/groups/${props.group.uuid}/application/genes`)
                                .then(response => response.data);

                genesAsText.value = genes.map(g => g.gene_symbol).join(", ");
            } catch (error) {
                console.log(error);
                store.commit('pushError', error.response.data);
            }
            
        }
        const save = async () => {
            try {
                const genes = genesAsText.value ? genesAsText.value.split(new RegExp(/[, \n]/)) : [];
                await api.post(`/api/groups/${props.group.uuid}/application/genes`, {genes});
                hideForm();
                context.emit('saved')
                getGenes();
            } catch (error) {
                console.log(error);
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors
                }
            }
        };

        onMounted(() => {
            syncGenesAsText();
            getGenes();
        })

        watch(() => props.group.expert_panel.genes, () => {
            syncGenesAsText();
        })

        return {
            genesAsText,
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