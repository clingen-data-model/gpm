import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import InlineTextDiff from './InlineTextDiff.vue'

const before = 'In feugiat nulla lorem, ac pulvinar odia hendrerit in.'
const after = 'In feugiat nulla lorem, ac pulvinar odio hendrerit in.'
const visibleText = element => {
  const copy = element.cloneNode(true)
  copy.querySelectorAll('.sr-only').forEach(label => label.remove())
  return copy.textContent
}
const changedText = (wrapper, selector) => wrapper.findAll(selector).map(node => visibleText(node.element))
const render = (before, after) => mount(InlineTextDiff, { props: { before, after } })

describe('InlineTextDiff word comparison', () => {
  it('marks only the replaced word and leaves punctuation and surrounding text intact', () => {
    const wrapper = render(before, after)
    expect(changedText(wrapper, 'del')).toEqual(['odia'])
    expect(changedText(wrapper, 'ins')).toEqual(['odio'])
    expect(visibleText(wrapper.element)).toBe('In feugiat nulla lorem, ac pulvinar odiaodio hendrerit in.')
    expect(wrapper.get('del').classes()).toContain('text-red-700')
    expect(wrapper.get('ins').classes()).toContain('text-green-800')
  })

  it('adds one word without removing unchanged text', () => {
    const wrapper = render('A scope.', 'A new scope.')
    expect(changedText(wrapper, 'ins').map(text => text.trim())).toEqual(['new'])
    expect(wrapper.find('del').exists()).toBe(false)
    expect(visibleText(wrapper.element)).toBe('A new scope.')
  })

  it('removes one word without adding unchanged text', () => {
    const wrapper = render('A new scope.', 'A scope.')
    expect(changedText(wrapper, 'del').map(text => text.trim())).toEqual(['new'])
    expect(wrapper.find('ins').exists()).toBe(false)
    expect(visibleText(wrapper.element)).toBe('A new scope.')
  })

  it('preserves an unchanged sentence and its whitespace', () => {
    const sentence = 'A scope, with punctuation!\n\tNext line.  '
    const wrapper = render(sentence, sentence)
    expect(wrapper.find('del, ins').exists()).toBe(false)
    expect(visibleText(wrapper.element)).toBe(sentence)
  })

  it('keeps a single word change minimal in a long scope description', () => {
    const prefix = 'Unchanged scope description. '.repeat(100)
    const suffix = '\nMore unchanged scope. '.repeat(100)
    const wrapper = render(prefix + before + suffix, prefix + after + suffix)
    expect(changedText(wrapper, 'del')).toEqual(['odia'])
    expect(changedText(wrapper, 'ins')).toEqual(['odio'])
    expect(visibleText(wrapper.element)).toBe(prefix + 'In feugiat nulla lorem, ac pulvinar odiaodio hendrerit in.' + suffix)
  })

  it('escapes markup in changed and unchanged text', () => {
    const wrapper = render('<script>alert(1)</script> old', '<script>alert(1)</script> <img>')
    expect(wrapper.find('script, img').exists()).toBe(false)
    expect(changedText(wrapper, 'ins')).toEqual(['<img>'])
    expect(visibleText(wrapper.element)).toContain('<script>alert(1)</script>')
  })
})
