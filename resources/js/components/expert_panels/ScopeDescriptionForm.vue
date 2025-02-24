<script>
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import MarkdownBlock from '@/components/MarkdownBlock.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import Group from '@/domain/group'
import GcepQuickGuideLink from '../links/GcepQuickGuideLink.vue';
import VcepProtocolLink from '../links/VcepProtocolLink.vue';

export default {
    name: "ScopeDescriptionForm",
    components: {
        GcepQuickGuideLink,
        VcepProtocolLink,
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
        scopeDescription: {
            get () {
                return this.group.expert_panel.scope_description;
            },
            set (value) {
                const groupCopy = this.group.clone();
                groupCopy.expert_panel.scope_description = value;
                this.$emit("update:group", groupCopy);
            }
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
            ></EditIconButton>
        </header>
        <div class="mt-2">
            <p class="text-sm">
                Describe the scope of work of the Expert Panel including
                the disease area(s), genes being addressed, and any
                specific rational for choosing the condition(s). See the
                <VcepProtocolLink v-if="group.is_vcep_or_scvcep" />
                <GcepQuickGuideLink v-if="group.is_gcep" /> for more
                information.
            </p>
            <transition name="fade" mode="out-in">
                <div v-if="editing" class="mt-2">
                    <RichTextEditor
                        v-model="group.expert_panel.scope_description"
                        :markdownFormat="true"
                        @update:modelValue="$emit('update')"
                    />
                </div>
                <div v-else class="border-2 mt-8 p-2">
                    <MarkdownBlock v-if="group.expert_panel.scope_description" :markdown="group.expert_panel.scope_description" />
                    <p v-else class="well cursor-pointer" @click="showForm">
                        A description of expertise has not yet been provided.
                    </p>
                </div>
            </transition>
        </div>
    </div>
</template>