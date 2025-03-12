const decToColor = {'approve': 'green', 'approve-after-revisions': 'blue', 'request-revisions': 'yellow'}

export const judgementColor = judgement => {
    if (!judgement.decision) return 'gray';
    return decToColor[judgement.decision];
}
