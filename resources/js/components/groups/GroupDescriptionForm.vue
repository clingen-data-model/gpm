<template>
    <div>
        <header class="flex justify-between items-center">
            <h4>Summary Description of Group</h4>
            <EditIconButton
                v-if="hasAnyPermission(['groups-manage', ['application-edit', group]]) && !editing"
                @click="$emit('update:editing', true)"
            ></EditIconButton>
        </header>
        <div class="mt-2">
            <transition name="fade" mode="out-in">
                <input-row 
                    v-if="editing"
                    v-model="group.description"
                    :errors="errors.description"
                    type="large-text"
                    vertical
                    @update:modelValue="$emit('update')"
                >
                    <template v-slot:label>
                        Describe the group's purpose, goals, and objectives as
                        intended for public display (e.g., at the clinicalgenome.org)
                        site. This may be more concise than the full scope of work.
                    </template>
                </input-row>
                <div v-else>
                    <markdown-block 
                        v-if="group.description" 
                        :markdown="group.description">
                    </markdown-block>
                    
                    <p class="well cursor-pointer" v-else @click="showForm">
                        A summary description has not yet been provided.
                    </p>
                </div>
            </transition>
        </div>
    </div>
</template>
<script>
import Group from '@/domain/group'
import EditIconButton from '@/components/buttons/EditIconButton.vue'

export default {
    name: "groupDescriptionForm",
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
        EditIconButton
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
            get: function () {
                return this.group.description;
            },
            set: function (value) {
                const groupCopy = this.group.clone();
                groupCopy.description = value;
                this.$emit("update:group", groupCopy);
            }
        }
    },
}
</script>