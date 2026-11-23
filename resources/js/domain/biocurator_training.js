// Reviewer display only: do not change the Step 4 eligibility requirements.
const flag = value => {
    if (value === true || value === 1 || value === '1') return true
    // NULL represents unchecked training; normalize only for this display.
    if (value === null || value === false || value === 0 || value === '0') return false
    return null
}
const flagLabel = value => flag(value) === null ? 'Unknown' : flag(value) ? 'Complete' : 'Not complete'

export function biocuratorTrainingCategories(group) {
    const categories = [
        { key: 'untrained', label: 'Untrained', members: [] },
        { key: 'level1', label: 'Level 1 Trained', members: [] },
        { key: 'level2', label: 'Level 2 Trained', members: [] },
        { key: 'verification', label: 'Training needs verification', members: [] },
    ]
    const people = new Map()
    // Reuse the same role and active-membership rules as the existing Step 4 card.
    for (const member of group.biocurators) {
        if (String(member.group_id) !== String(group.id) || member.deleted_at) continue
        const key = member.person_id ?? member.person?.id ?? `membership-${member.id}`
        const personKey = String(key)
        if (!people.has(personKey)) people.set(personKey, [])
        people.get(personKey).push(member)
    }
    for (const memberships of people.values()) {
        const member = memberships[0]
        const combinations = memberships.map(m => [flag(m.training_level_1), flag(m.training_level_2)])
        const conflict = new Set(combinations.map(pair => JSON.stringify(pair))).size > 1
        const [level1, level2] = combinations[0]
        const category = conflict || level1 === null || level2 === null || (!level1 && level2)
            ? 3 : level2 ? 2 : level1 ? 1 : 0
        const details = conflict
            ? 'Conflicting training records for this membership. Please verify.'
            : `Level 1: ${flagLabel(member.training_level_1)}; Level 2: ${flagLabel(member.training_level_2)}.`
        categories[category].members.push({ member, details })
    }
    return categories
}
