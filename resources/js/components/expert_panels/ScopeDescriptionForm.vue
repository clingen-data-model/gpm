<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Description of Scope</h4>
            <edit-icon-button 
                v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
                @click="$emit('update:editing', true)"
            ></edit-icon-button>
        </header>
        <div class="mt-2">
            <p class="text-sm">
                Describe the scope of work of the Expert Panel including
                the disease area(s), genes being addressed, and any
                specific rational for choosing the condition(s). See the
                <vcep-protocol-link v-if="group.is_vcep_or_scvcep" />
                <gcep-quick-guide-link v-if="group.is_gcep" /> for more
                information.
            </p>
            <transition name="fade" mode="out-in">
                <div v-if="editing" class="mt-2">
                    <MarkdownEditor
                        v-model="group.expert_panel.scope_description"
                        @update:modelValue="$emit('update')"
                    />
                </div>
                <div v-else class="border-2 mt-8 p-2">
                    <div v-if="group.expert_panel.scope_description" v-html="htmlDescription" />
                    <p class="well cursor-pointer" v-else @click="showForm">
                        A description of expertise has not yet been provided.
                    </p>
                </div>
            </transition>
        </div>
    </div>
</template>
<script>
import Group from '@/domain/group'
import GcepQuickGuideLink from '../links/GcepQuickGuideLink.vue';
import VcepProtocolLink from '../links/VcepProtocolLink.vue';
import EditIconButton from '@/components/buttons/EditIconButton.vue'
import { htmlFromMarkdown } from '@/markdown-utils'
import MarkdownEditor from '@/components/prosekit/MarkdownEditor.vue'

export default {
    name: "scopeDescriptionForm",
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
    components: {
        EditIconButton,
        MarkdownEditor
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
            get: function () {
                return this.group.expert_panel.scope_description;
            },
            set: function (value) {
                const groupCopy = this.group.clone();
                groupCopy.expert_panel.scope_description = value;
                this.$emit("update:group", groupCopy);
            }
        },
        htmlDescription: function () {
            return htmlFromMarkdown(this.group.expert_panel.scope_description);
        },
    },
    components: {
        GcepQuickGuideLink,
        VcepProtocolLink,
        EditIconButton,
        MarkdownEditor,
    }
}
</script>