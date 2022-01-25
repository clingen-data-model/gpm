<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Description of Expertise</h4>
            <edit-icon-button 
                v-if="hasAnyPermission(['groups-manage'], ['edit-info', group]) && !editing"
                @click="$emit('update:editing', true)"
            ></edit-icon-button>
        </header>
        <div class="mt-4">
            <transition name="fade" mode="out-in">
                <input-row 
                    v-model="group.expert_panel.membership_description"
                    label="Describe the expertise of VCEP members who regularly use the ACMG/AMP guidelines to classify variants and/or review variants during clinical laboratory case sign-out." 
                    v-if="editing" 
                    :errors="errors.membership_description"
                    type="large-text"
                    vertical
                    @update:modelValue="emitUpdate"
                />
                <div v-else>
                    <markdown-block 
                        v-if="group.expert_panel.membership_description" :markdown="group.expert_panel.membership_description">
                    </markdown-block>
                    <p class="well" v-else>
                        A description of expertise has not yet been provided.
                    </p>
                </div>
            </transition>
        </div>
    </div>
</template>
<script>
export default {
    name: 'MembershipDescriptionForm',
    props: {
        editing: {
            type: Boolean,
            required: false,
            default: false
        },
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    emits: [
        'update:editing',
        'saved',
        'canceled',
        'update'
    ],
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set (value) {
                this.$store.commit('groups/addItem', value);
            }
        }
    },
    methods: {
        emitUpdate () {
            this.$emit('update');
        }
    }
}
</script>