<script setup>
import { computed } from 'vue'
import useEmitCheckpoints from '@/composables/useEmitCheckpoints'

const props = defineProps({
    ids: { type: [Number, Array], default: null },
    group: { type: Object, default: null },

    onlyActive: { type: Boolean, default: true },
    queue: { type: Boolean, default: true },

    label: { type: String, default: 'Emit' },
    processingLabel: { type: String, default: 'Queuing...' },
    size: { type: String, default: 'btn-xs' },
})

const { isActive, emitCheckpoints, emitting, emittingRow } = useEmitCheckpoints()

const resolvedIds = computed(() => {
    if (Array.isArray(props.ids)) return props.ids
    if (typeof props.ids === 'number') return [props.ids]
    if (props.group?.id) return [props.group.id]
    return []
})

const rowId = computed(() => props.group?.id ?? null)
const processing = computed(() => rowId.value ? !!emittingRow[rowId.value] : emitting.value)

const disabled = computed(() => {
    if (resolvedIds.value.length === 0) return true
    if (props.onlyActive && props.group && !isActive(props.group)) return true
        return processing.value
})

const onClick = async () => {
    await emitCheckpoints(resolvedIds.value, {
        rowId: rowId.value ?? undefined,
        queue: props.queue,
        toast: true,
    })
}
</script>

<template>
    <button type="button" class="btn btn-outline" v-if="! disabled" :class="size" @click.stop="onClick" :title="disabled ? 'Only Active groups can be checkpointed' : 'Emit checkpoint'">
        <span v-if="processing">{{ processingLabel }}</span>
        <span v-else>{{ label }}</span>
    </button>
</template>
