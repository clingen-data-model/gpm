<script>
import Group from '@/domain/group'
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import MarkdownBlock from '@/components/MarkdownBlock.vue'

export default {
    name: "GroupDescriptionForm",
    components: {
        EditIconButton,
        RichTextEditor,
        MarkdownBlock,
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
        description: {
            get () {
                return this.group.description;
            },
            set (value) {
                const groupCopy = this.group.clone();
                groupCopy.description = value;
                this.$emit("update:group", groupCopy);
            }
        },
    },
}
</script>
<template>
  <div>
    <header class="flex justify-between items-center">
      <h4>Summary Description of Group</h4>
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
            :markdownFormat="true"
            @update:model-value="$emit('update')"
          />
        </div>
        <div v-else class="border-2 mt-8 p-2">
          <MarkdownBlock v-if="group.description" :markdown="group.description" />
          <p v-else class="well cursor-pointer" @click="showForm">
            A summary description has not yet been provided.
          </p>
        </div>
      </transition>
    </div>
  </div>
</template>