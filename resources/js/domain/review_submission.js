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
