// Compile the real shared CSS: background-color alone cannot test a painted gradient.
import { readFileSync } from 'node:fs'
import postcss from 'postcss'
import tailwindcss from 'tailwindcss'
import { it, expect } from 'vitest'

it('paints red gradient stops, darker hover and white text; opacity is disabled-only', async () => {
  const css = readFileSync('resources/js/assets/styles/app.css', 'utf8')
  const result = await postcss([tailwindcss({ content: [{ raw: '<button class="btn red inline-block">Remove</button>' }] })]).process(css, { from: undefined })
  const declarations = selector => {
    const values = {}
    result.root.walkRules(selector, rule => rule.walkDecls(decl => { values[decl.prop] = decl.value }))
    return values
  }
  const red = declarations('.btn.red')
  expect(red['background-image']).toContain('linear-gradient')
  expect(red['--tw-gradient-from']).toContain('#ef4444')
  expect(red['--tw-gradient-to']).toContain('#ef4444')
  expect(red.color).toContain('255 255 255')
  expect(declarations('.btn.red:hover')['--tw-gradient-to']).toContain('#b91c1c')
  expect(declarations('.btn').opacity).toBeUndefined()
  expect(red.opacity).toBeUndefined()
  expect(declarations('.btn:disabled').opacity).toBe('0.6')
})
