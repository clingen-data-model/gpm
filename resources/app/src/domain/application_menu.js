import MenuItem from '@/MenuItem.js';

const goToAnchor = (anchor) => {
    location.href = "#";
    location.href = `#${anchor}`
}

const goToSection = (section) => {
    return () => {
        goToAnchor(section)
    }
}

const goToPage = (page) => {
    return () => {
        goToAnchor(page)
    }
}

export const gcepMenu = [
    new MenuItem({label: 'Basic Information', handler: goToSection('basicInfo')}),
    new MenuItem({label: 'Scope Definition', handler: goToSection('scope')}),
    new MenuItem({label: 'Attestations', handler: goToSection('attestations')}),
    new MenuItem({label: 'Ongoing Curation', handler: goToSection('reanalysis')}),
    new MenuItem({label: 'NHGRI Data Availability', handler: goToSection('nhgri')}),
]

export const vcepMenu = [
    new MenuItem({
        label: 'Group Definition',
        contents: [
            new MenuItem({label: 'Basic Information', handler: goToSection('basicInfo')}),
            new MenuItem({label: 'Scope Definition', handler: goToSection('scope')}),
            new MenuItem({label: 'Reanalysis & Discrepancy Resolution', handler: goToSection('reanalysis')}),
            new MenuItem({label: 'NHGRI Data Availability', handler: goToSection('nhgri')}),
        ],
        handler: goToPage('definition'),
    }),
    new MenuItem({
        label: 'Draft Specifications',
        handler: goToPage('draft-specifications'),
    }),
    new MenuItem({
        label: 'Pilot Specifications',
        handler: goToPage('pilot-specifications'),
    }),
    new MenuItem({
        label: 'Sustained Curation',
        handler: goToPage('sustained-curation'),
        contents: [
            new MenuItem({
                label: 'Curation and Review Process',
                handler: goToSection('curationReviewProcess'),
            }),
            new MenuItem({
                label: 'Evidence Summaries',
                handler: goToSection('evidenceSummaries'),
            }),
            new MenuItem({
                label: 'Member Designation',
                handler: goToSection('designations'),
            }),
        ]
    })
];