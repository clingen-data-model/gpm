<script>
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import { htmlFromMarkdown } from '@/markdown-utils';

export default {
    name: 'MembershipDescriptionForm',
    components: {
        EditIconButton,
        RichTextEditor,
    },
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
        },
        htmlMembershipDescription () {
            return htmlFromMarkdown(this.group.expert_panel.membership_description);
        },
        application () {
            return this.group.expert_panel;
        },
        canEdit() {
            if (this.application.stepIsApproved(1)) {
                return this.hasRole('super-admin');
            }
            return this.hasAnyPermission(['groups-manage', ['application-edit', this.group]]);
        }
    },
    methods: {
        emitUpdate () {
            this.$emit('update');
        }
    }
}
</script>
<template>
  <div>
    <header class="flex justify-between items-center">
      <h4>Description of Expertise</h4>
      <EditIconButton v-if="canEdit && !editing" @click="$emit('update:editing', true)" />
    </header>
    <div class="mt-4">
      <p class="text-sm">
        Describe the expertise of VCEP members who regularly use the ACMG/AMP guidelines to classify variants and/or review variants during clinical laboratory case sign-out.
      </p>
      <transition name="fade" mode="out-in">
        <div v-if="editing" class="mt-2">
          <RichTextEditor
            v-model="group.expert_panel.membership_description"
            @update:model-value="emitUpdate"
          />
        </div>
        <div v-else class="border-2 mt-8 p-2">
          <div v-if="group.expert_panel.membership_description" v-html="htmlMembershipDescription" />
          <p v-else class="well cursor-pointer" @click="showForm">
            A description of expertise has not yet been provided.
          </p>
        </div>
      </transition>
    </div>
  </div>
</template>
