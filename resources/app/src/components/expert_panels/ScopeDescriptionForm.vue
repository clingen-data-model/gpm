<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Description of Scope</h4>
            <edit-button 
                v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
                @click="showForm"
            ></edit-button>
        </header>
        <div class="mt-2">
            <transition name="fade" mode="out-in">
                <input-row :vertical="true" label="Describe the scope of work of the Expert Panel including the disease area(s), genes being addressed, and any specific rational for choosing the condition(s)." v-if="editing" :errors="errors.scope_description">
                    <textarea class="w-full" rows="10" v-model="scopeDescription"></textarea>
                    <button-row @submit="save" @cancel="hideForm"></button-row>
                </input-row>
                <div v-else>
                    <markdown-block 
                        v-if="scopeDescription" 
                        :markdown="scopeDescription">
                    </markdown-block>
                    <p class="well cursor-pointer" v-else @click="showForm">
                        A description of expertise has not yet been provided.
                    </p>
                </div>
            </transition>
        </div>
    </div>
</template>
<script>
import {ref, onMounted} from 'vue'
import {useStore} from 'vuex'
import Group from '@/domain/group'
import EditButton from '@/components/buttons/EditIconButton'
import is_validation_error from '../../http/is_validation_error'
import { hasAnyPermission } from '@/auth_utils'

export default {
    name: 'scopeDescriptionForm',
    components: {
        EditButton
    },
    props: {
        group: {
            type: Group,
            required: true
        }
    },
    emits: [
        'editing',
        'saved',
        'canceled',
    ],
    setup (props, context) {
        const store = useStore();

        let errors = ref({});
        const editing = ref(false)
        const hideForm = () => {
            editing.value = false;
            errors.value = {};
        }
        const showForm = () => {
            if (hasAnyPermission(['ep-applications-manage', ['application-edit', props.group]])) {
                errors.value = {};
                editing.value = true;
                context.emit('editing');
            }
        }
        const cancel = () => {
            hideForm();
            context.emit('canceled');
        }
        const scopeDescription = ref(null)
        const syncDescription = () => {
            const {scope_description} = props.group.expert_panel;
            scopeDescription.value = scope_description;
        }
        const save = async () => {
            console.log('save!')
            try {
                store.dispatch(
                    'groups/scopeDescriptionUpdate', 
                    {
                        uuid: props.group.uuid, 
                        scopeDescription: scopeDescription.value
                    }
                );
                hideForm();
                context.emit('saved');
            } catch (error) {
                if (is_validation_error(error)) {
                    errors.value = error.response.data.errors
                }
            }
        }

        onMounted(() => {
            syncDescription();
        })

        return {
            editing,
            showForm,
            hideForm,
            cancel,
            errors,
            scopeDescription,
            syncDescription,
            save,
        }

    },
}
</script>