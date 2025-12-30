<script>
import Group from '@/domain/group'
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import { htmlFromMarkdown } from '@/markdown-utils';

export default {
    name: "GcepRationaleForm",
    components: {
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
      htmlRationale() {
          return htmlFromMarkdown(this.group.expert_panel.gcep_rationale || '');
      },
      expertPanel() {
        return this.group?.expert_panel || {};
      },
      genes() {
        const g = this.expertPanel?.genes;
        return Array.isArray(g) ? g : [];
      },
      tier1CuratedGenesCount() {
        return this.genes.reduce((n, g) => { return n + ((this.getTier(g) === 1 && this.isCurated(g)) ? 1 : 0); }, 0);
      },
      shouldShowRationale() {
        return this.group?.is_gcep && (this.tier1CuratedGenesCount > 0 || this.editing);
      },
    },
    methods: {
      isCurated(g) {
        return Array.isArray(g?.details) && g.details.length > 0;
      },
      getTier(g) {
        const n = Number(g?.tier);
        return Number.isFinite(n) ? n : null;
      },
      showForm() {
        this.$emit('update:editing', true);
      }
    }

}
</script>
<template>
  <div v-if="shouldShowRationale">
    <hr />
    <header class="flex justify-between items-center">
      <h4>Rationale</h4>
      <EditIconButton
        v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
        @click="$emit('update:editing', true)"
      />
    </header>
    <div class="mt-2 text-sm">
      <div v-if="tier1CuratedGenesCount > 0" role="note" class="mt-3 border-l-4 border-amber-400 bg-amber-50 px-3 py-2 text-sm text-amber-900 rounded-md dark:border-amber-400 dark:bg-amber-900/20 dark:text-amber-100">
        <strong>Tier 1 curated genes added: {{ tier1CuratedGenesCount }}</strong>
        — Please include rationale for how you prioritized your list and how you will handle any overlap in curation efforts, especially for genes that are in another GCEPs scope.
      </div>
      <transition name="fade" mode="out-in">
        <div v-if="editing" class="mt-2">
          <RichTextEditor v-model="group.expert_panel.gcep_rationale" @update:model-value="$emit('update')" />
        </div>
        <div v-else class="border-2 mt-8 p-2">
          <div v-if="group.expert_panel.gcep_rationale" v-html="htmlRationale" />
          <p v-else class="well cursor-pointer" @click="showForm">A description of rationale has not yet been provided.</p>
        </div>
      </transition>
    </div>
  </div>
</template>
