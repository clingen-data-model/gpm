import configs from '@/configs'

export const submissionReviewStep = submission => {
  const approvalStep = Number(submission?.data?.approval_step)
  if ([1, 4].includes(approvalStep)) return approvalStep
  const typeId = Number(submission?.submission_type_id ?? submission?.type?.id)
  for (const step of [1, 4]) {
    if (typeId === Number(configs.submissions.typesByStep[step]?.id)) return step
  }
  return ({ Definition: 1, 'Sustained Curation': 4 })[submission?.type?.name] ?? null
}

// Initial rounds are independent of SoW revisions and of the panel's current step.
export const latestInitialSubmissionForStep = (submissions, step) => [...(submissions ?? [])]
  .filter(submission => !submission.deleted_at && submission.data?.context !== 'scope_of_work_revision'
    && submissionReviewStep(submission) === Number(step))
  .sort((a, b) => Number(b.id) - Number(a.id))[0] ?? null

export const reviewStepLabel = step => ({ 1: 'Step 1: Group Definition', 4: 'Step 4: Sustained Curation' })[step]

export const activeScopeOfWorkReviewSubmission = (group, latest) => {
  const submissions = [...(group?.expert_panel?.submissions ?? [])]
  if (latest?.id && (!latest.group_id || Number(latest.group_id) === Number(group?.id))) {
    const index = submissions.findIndex(item => Number(item.id) === Number(latest.id))
    if (index >= 0) submissions[index] = latest
    else submissions.push(latest)
  }
  const newest = submissions.filter(item => !item.deleted_at
    && (item.scope_of_work_version_id || item.data?.context === 'scope_of_work_revision'))
    .sort((a, b) => Number(b.id) - Number(a.id))[0] ?? null
  // Do not revive an earlier revisions-requested round after a newer round is approved.
  return [1, 2, 3].includes(Number(newest?.submission_status_id)) ? newest : null
}

export const initialReviewStep = (group, latest) => {
  if (group?.is_gcep || group?.expert_panel?.is_gcep) return 1
  const revision = activeScopeOfWorkReviewSubmission(group, latest)
  if (revision) {
    const approvalStep = Number(revision.data?.approval_step)
    const typeStep = submissionReviewStep({ ...revision, data: {} })
    if ([1, 4].includes(approvalStep) && typeStep && approvalStep !== typeStep) return 1
    return submissionReviewStep(revision) ?? 1
  }
  const step = Number(group?.expert_panel?.current_step)
  return [1, 2, 3, 4].includes(step) ? step : 1
}
