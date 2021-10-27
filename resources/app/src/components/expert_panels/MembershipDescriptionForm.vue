<template>
    <div class="mt-8">
        <header class="flex justify-between items-center">
            <h4>Description of Expertise</h4>
            <edit-button 
                v-if="hasAnyPermission(['groups-manage'], ['edit-info', group]) && !editing"
                @click="showForm"
            ></edit-button>
        </header>
        <div class="mt-4">
            <transition name="fade" mode="out-in">
                <input-row :vertical="true" label="Describe the expertise of VCEP members who regularly use the ACMG/AMP guidelines to classify variants and/or review variants during clinical laboratory case sign-out." v-if="editing" :errors="errors.membership_description">
                    <textarea class="w-full" rows="10" v-model="membershipDescription"></textarea>
                    <button-row @submit="save" @cancel="hideForm"></button-row>
                </input-row>
                <div v-else>
                    <div class="markdown" v-if="membershipDescription" v-html="marked(membershipDescription)"></div>
                    <p class="well" v-else>
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
import marked from 'marked'

export default {
    name: 'MembershipDescriptionForm',
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
            errors.value = {};
            editing.value = true;
            context.emit('editing');
        }
        const cancel = () => {
            hideForm();
            context.emit('canceled');
        }
        const membershipDescription = ref(null)
        const syncDescription = () => {
            const {membership_description} = props.group.expert_panel;
            membershipDescription.value = membership_description;
        }
        const save = async () => {
            console.log('save!')
            try {
                store.dispatch(
                    'groups/membershipDescriptionUpdate', 
                    {
                        uuid: props.group.uuid, 
                        membershipDescription: membershipDescription.value
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
            membershipDescription,
            syncDescription,
            save,
            marked
        }

    },
}
</script>