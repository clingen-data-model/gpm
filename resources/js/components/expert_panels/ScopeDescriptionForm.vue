<script>
import Group from '@/domain/group'
import GcepQuickGuideLink from '../links/GcepQuickGuideLink.vue';
import VcepProtocolLink from '../links/VcepProtocolLink.vue';
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import { htmlFromMarkdown } from '@/markdown-utils';

export default {
    name: "ScopeDescriptionForm",
    components: {
        GcepQuickGuideLink,
        VcepProtocolLink,
        EditIconButton,
        RichTextEditor,
    },
    props: {
        editing: {
            type: Boolean,
            required: false,
            default: true
        },
        errors: {
            type: Object,
            required: false,
            default: () => ({}),
        },
    },
    emits: [
        "update:editing",
        "update:group",
        "update"
    ],
    computed: {
        group: {
            get() {
                return this.$store.getters["groups/currentItem"] || new Group();
            },
            set(value) {
                this.$store.commit("groups/addItem", value);
            }
        },
        htmlScopeDescription() {
            return htmlFromMarkdown(this.group.expert_panel.scope_description);
        },
        expertPanel() {
          return this.group?.expert_panel || {};
        },
        genes() {
          const g = this.expertPanel?.genes;
          return Array.isArray(g) ? g : [];
        },
    }
}
</script>
<template>
  <div>
    <header class="flex justify-between items-center">
      <h4>Description of Scope</h4>
      <EditIconButton
        v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
        @click="$emit('update:editing', true)"
      />
    </header>
    <div class="mt-2 text-sm">
      <p v-if="group.is_vcep_or_scvcep">
        Describe the scope of work of the Expert Panel including the disease area(s), genes being addressed, and any specific rationale for choosing the condition(s). See the
        <VcepProtocolLink /> for more information.
      </p>
      <div v-if="group.is_gcep">
        Describe the scope of work of the expert panel including the following:
      
        <ul class="list-disc list-inside mt-2">
          <li>Describe the disease area of focus</li>
          <li>Indicate why curation efforts would benefit this disease area</li>
          <li>Indicate how the gene list will be prioritized</li>
          <li>Describe plans to collaborate with other GCEPs on any genes on your gene list that overlap in scope</li>
          <li>see the <GcepQuickGuideLink /> for more information</li>
        </ul>
      </div>

      <transition name="fade" mode="out-in">
        <div v-if="editing" class="mt-2">
          <RichTextEditor
            v-model="group.expert_panel.scope_description"
            @update:model-value="$emit('update')"
          />
        </div>
        <div v-else class="border-2 mt-8 p-2">
          <div v-if="group.expert_panel.scope_description" v-html="htmlScopeDescription" />
          <p v-else class="well cursor-pointer" @click="showForm">
            A description of scope has not yet been provided.
          </p>
        </div>
      </transition>
    </div>
  </div>
</template>
