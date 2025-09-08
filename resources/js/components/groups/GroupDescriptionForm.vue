<script>
import Group from '@/domain/group'
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import { htmlFromMarkdown } from '@/markdown-utils';

export default {
    name: "GroupDescriptionForm",
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
        htmlDescription() {
            return htmlFromMarkdown(this.group.description);
        }
    },
}
</script>
<template>
  <div>
    <header class="flex justify-between items-center">
      <h4>Website Summary Description of Group</h4>
      <EditIconButton
        v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
        @click="$emit('update:editing', true)"
      />
    </header>
    <div class="mt-2">
      <p class="text-sm">
        This is a description of the group's purpose, goals, and objectives
        as intended for public display (e.g., at the clinicalgenome.org
        site). This may be more concise than the full scope of work.
      </p>
      <transition name="fade" mode="out-in">
        <div v-if="editing" class="mt-2">
          <RichTextEditor
            v-model="group.description"
            @update:model-value="$emit('update')"
          />
        </div>
        <div v-else class="border-2 mt-8 p-2">
          <div v-if="group.description" v-html="htmlDescription" />
          <p v-else class="well cursor-pointer" @click="showForm">
            A website summary description has not yet been provided.
          </p>
        </div>
      </transition>
    </div>
  </div>
</template>
