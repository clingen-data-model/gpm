<script>
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import DOMPurify from 'dompurify';

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
        sanitizedMembershipDescription () {
            return DOMPurify.sanitize(this.group.expert_panel.membership_description);
        },
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
      <EditIconButton 
        v-if="hasAnyPermission(['groups-manage'], ['edit-info', group]) && !editing"
        @click="$emit('update:editing', true)"
      />
    </header>
    <div class="mt-4">
      <p class="text-sm">
        This is a description of the group's purpose, goals, and objectives
        as intended for public display (e.g., at the clinicalgenome.org
        site). This may be more concise than the full scope of work.
      </p>
      <transition name="fade" mode="out-in">
        <div v-if="editing" class="mt-2">
          <RichTextEditor
            v-model="group.expert_panel.membership_description"
            @update:model-value="emitUpdate"
          />
        </div>
        <div v-else class="border-2 mt-8 p-2">
          <div v-if="group.expert_panel.membership_description" v-html="sanitizedMembershipDescription" />
          <p v-else class="well cursor-pointer" @click="showForm">
            A description of expertise has not yet been provided.
          </p>
        </div>
      </transition>
    </div>
  </div>
</template>