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
                    :vertical="true" 
                    label="Describe the expertise of VCEP members who regularly use the ACMG/AMP guidelines to classify variants and/or review variants during clinical laboratory case sign-out." 
                    v-if="editing" 
                    :errors="errors.membership_description"
                >
                    <textarea class="w-full" rows="10" v-model="membershipDescription"></textarea>
                    <!-- <button-row @submit="save" @cancel="hideForm"></button-row> -->
                </input-row>
                <div v-else>
                    <markdown-block 
                        v-if="membershipDescription" :markdown="membershipDescription">
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
import Group from '@/domain/group'

export default {
    name: 'MembershipDescriptionForm',
    props: {
        group: {
            type: Group,
            required: true
        },
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
        'update:group',
        'saved',
        'canceled',
    ],
    computed: {
        membershipDescription: { 
            get: function () {
                return this.group.expert_panel.membership_description
            }, 
            set: function (value) {
                const groupCopy = this.group.clone();
                groupCopy.expert_panel.membership_description = value;
                this.$emit('update:group', groupCopy);
            }
        }
    }
}
</script>