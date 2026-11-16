import { expect, it, vi } from 'vitest'
import { flushPromises } from '@vue/test-utils'
import AnnualUpdateForm from '@/views/AnnualUpdateForm.vue'
import { api } from '@/http'
vi.mock('@/http', () => ({ api: { post: vi.fn(), put: vi.fn() }, isValidationError: () => false }))

it('waits for outstanding saves before posting, then uses server snapshots', async () => {
  let release
  const ctx = {
    annualUpdate: { id: 1, data: { publications_reviewed: true } }, group: { uuid: 'g' },
    debounceSave: { cancel: vi.fn() }, saveQueue: new Promise(resolve => { release = resolve }),
    save: vi.fn(async () => { expect(ctx.annualUpdate.data.publications_reviewed).toBe(true) }),
  }
  const snapshot = { id: 1, completed_at: '2026-01-01', data: { publications: [{ publication_id: 'chosen', title: 'Snapshot' }] } }
  api.post.mockResolvedValue({ data: snapshot })
  const pending = AnnualUpdateForm.methods.submit.call(ctx)
  await flushPromises()
  expect(api.post).not.toHaveBeenCalled()
  release()
  await pending
  expect(ctx.save).toHaveBeenCalledOnce()
  expect(ctx.annualUpdate).toEqual(snapshot)
  expect(ctx.submitting).toBe(false)
})

it('serializes draft writes and skips completed updates', async () => {
  let release
  const ctx = {
    annualUpdate: { id: 1, data: { publications_reviewed: true } },
    persistDraft: vi.fn().mockImplementationOnce(() => new Promise(resolve => { release = resolve })).mockResolvedValue(undefined),
  }
  const first = AnnualUpdateForm.methods.save.call(ctx)
  await flushPromises()
  const second = AnnualUpdateForm.methods.save.call(ctx)
  await flushPromises()
  expect(ctx.persistDraft).toHaveBeenCalledTimes(1)
  release()
  await Promise.all([first, second])
  expect(ctx.persistDraft).toHaveBeenCalledTimes(2)
  ctx.annualUpdate.completed_at = '2026-01-01'
  await AnnualUpdateForm.methods.save.call(ctx)
  expect(ctx.persistDraft).toHaveBeenCalledTimes(2)
})
