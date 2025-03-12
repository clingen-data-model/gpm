import { defineBasicExtension } from 'prosekit/basic'
import { union } from 'prosekit/core'
import { defineHorizontalRule } from 'prosekit/extensions/horizontal-rule'

export function defineExtension() {
    return union(
        defineBasicExtension(),
        defineHorizontalRule(),
    )
}

export type EditorExtension = ReturnType<typeof defineExtension>