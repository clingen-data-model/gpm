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
    new MenuItem({label: 'Basic Information', id: 'basicInfo', handler: goToSection('basicInfo')}),
    new MenuItem({label: 'Scope Definition', id: 'scope', handler: goToSection('scope')}),
    new MenuItem({label: 'Attestations', id: 'attestations', handler: goToSection('attestations')}),
    new MenuItem({label: 'Ongoing Curation', id: 'reanalysis', handler: goToSection('reanalysis')}),
    new MenuItem({label: 'NHGRI Data Availability', id: 'nhgri', handler: goToSection('nhgri')}),
]

export const vcepMenu = [
    new MenuItem({
        label: 'Group Definition',
        id: 'definition', 
        contents: [
            new MenuItem({label: 'Basic Information', id: 'basicInfo', handler: goToSection('basicInfo')}),
            new MenuItem({label: 'Scope Definition', id: 'scope', handler: goToSection('scope')}),
            new MenuItem({label: 'Reanalysis & Discrepancy Resolution', id: 'reanalysis', handler: goToSection('reanalysis')}),
            new MenuItem({label: 'NHGRI Data Availability', id: 'nhgri', handler: goToSection('nhgri')}),
        ],
        handler: goToPage('definition'),
    }),
    new MenuItem({
        label: 'Specifications Development',
        id: 'specifications-development', 
        handler: goToPage('specifications-development'),
    }),
    // new MenuItem({
    //     label: 'Draft Specifications',
    //     id: 'draft-specifications', 
    //     handler: goToPage('draft-specifications'),
    // }),
    // new MenuItem({
    //     label: 'Pilot Specifications',
    //     id: 'pilot-specifications', 
    //     handler: goToPage('pilot-specifications'),
    // }),
    new MenuItem({
        label: 'Sustained Curation',
        id: 'sustained-curation', 
        handler: goToPage('sustained-curation'),
        contents: [
            new MenuItem({
                label: 'Curation and Review Process',
                id: 'curationReviewProcess', 
                handler: goToSection('curationReviewProcess'),
            }),
            new MenuItem({
                label: 'Evidence Summaries',
                id: 'evidenceSummaries', 
                handler: goToSection('evidenceSummaries'),
            }),
            new MenuItem({
                label: 'Member Designation',
                id: 'designations', 
                handler: goToSection('designations'),
            }),
        ]
    })
];