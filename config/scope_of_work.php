<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scope of Work Versioning
    |--------------------------------------------------------------------------
    |
    | This config defines which post-approval changes are considered Scope of
    | Work changes, whether they are major/minor, whether approval is required,
    | and which review step/approver group should be used.
    |
    */

    'versioning' => [
        'initial_major_version' => 1,
        'initial_minor_version' => 0,
    ],

    'group_types' => [
        'gcep' => 'gcep',
        'vcep' => 'vcep',
        'scvcep' => 'scvcep',
    ],

    'impact' => [
        'major' => 'major',
        'minor' => 'minor',
    ],

    'approval' => [
        'yes' => true,
        'no' => false,
        'conditional' => 'conditional',
    ],

    'approval_steps' => [
        'definition' => 1,
        'sustained_curation' => 4,
    ],

    'approvers' => [
        'none' => [
            'label' => 'No approval required',
        ],

        'oc_chairs' => [
            'label' => 'OC Chairs',
        ],

        'cdwg_oc_chairs' => [
            'label' => 'CDWG OC Chairs',
        ],
    ],

    'roles' => [
        'coordinator' => 'coordinator',
        'chair' => 'chair',
        'grant_liaison' => 'grant-liaison',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope of Work Rules
    |--------------------------------------------------------------------------
    |
    | Keys should be stable because they may be stored with detected changes.
    |
    */

    'rules' => [
        'panel_name.rename' => [
            'area' => 'panel_name',
            'change_type' => 'rename',
            'label' => 'Rename Expert Panel',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'major',
            'requires_approval' => true,
            'approval_step' => 1,
            'approvers' => 'cdwg_oc_chairs',
        ],

        'gene.add' => [
            'area' => 'gene_list',
            'change_type' => 'add',
            'label' => 'Add gene',
            'groups' => [
                'vcep' => [
                    'impact' => 'major',
                    'requires_approval' => true,
                    'approval_step' => 1,
                    'approvers' => 'oc_chairs',
                ],
                'scvcep' => [
                    'impact' => 'major',
                    'requires_approval' => true,
                    'approval_step' => 1,
                    'approvers' => 'oc_chairs',
                ],
                'gcep' => [
                    'impact' => 'major',
                    'requires_approval' => 'conditional',
                    'condition' => 'scope_change_indicated',
                    'approval_step' => 1,
                    'approvers' => 'cdwg_oc_chairs',
                ],
            ],
        ],

        'gene.remove' => [
            'area' => 'gene_list',
            'change_type' => 'remove',
            'label' => 'Remove gene',
            'groups' => [
                'vcep' => [
                    'impact' => 'major',
                    'requires_approval' => true,
                    'approval_step' => 1,
                    'approvers' => 'oc_chairs',
                ],
                'scvcep' => [
                    'impact' => 'major',
                    'requires_approval' => true,
                    'approval_step' => 1,
                    'approvers' => 'oc_chairs',
                ],
                'gcep' => [
                    'impact' => 'major',
                    'requires_approval' => 'conditional',
                    'condition' => 'large_chunk_removed',
                    'approval_step' => 1,
                    'approvers' => 'cdwg_oc_chairs',
                ],
            ],
        ],
        'gene.update_tier' => [
            'area' => 'gene_list',
            'change_type' => 'update_tier',
            'label' => 'Update gene tier',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'major',
            'requires_approval' => true,
            'approval_step' => 1,
            'approvers' => 'oc_chairs',
        ],
        'gene.update' => [
            'area' => 'gene_list',
            'change_type' => 'update',
            'label' => 'Update gene',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'major',
            'requires_approval' => true,
            'approval_step' => 1,
            'approvers' => 'oc_chairs',
        ],
        'scope_description.update' => [
            'area' => 'scope_description',
            'change_type' => 'update',
            'label' => 'Update Scope of Work description',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'minor',
            'requires_approval' => false,
            'approval_step' => null,
            'approvers' => 'none',
        ],

        'member.add' => [
            'area' => 'membership',
            'change_type' => 'add_member',
            'label' => 'Add member',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'minor',
            'requires_approval' => false,
            'approval_step' => null,
            'approvers' => 'none',
        ],

        'member.remove' => [
            'area' => 'membership',
            'change_type' => 'remove_member',
            'label' => 'Remove member',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'minor',
            'requires_approval' => false,
            'approval_step' => null,
            'approvers' => 'none',
        ],

        'member.update_role' => [
            'area' => 'membership',
            'change_type' => 'update_role',
            'label' => 'Update member role',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'minor',
            'requires_approval' => false,
            'approval_step' => null,
            'approvers' => 'none',
        ],

        'member.add_chair' => [
            'area' => 'membership',
            'change_type' => 'add_chair',
            'label' => 'Add chair',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'major',
            'requires_approval' => true,
            'approval_step' => 1,
            'approvers' => 'oc_chairs',
        ],

        'member.remove_chair' => [
            'area' => 'membership',
            'change_type' => 'remove_chair',
            'label' => 'Remove chair',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'major',
            'requires_approval' => true,
            'approval_step' => 1,
            'approvers' => 'oc_chairs',
        ],

        'member.add_liaison' => [
            'area' => 'membership',
            'change_type' => 'add_liaison',
            'label' => 'Add liaison',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'minor',
            'requires_approval' => false,
            'approval_step' => null,
            'approvers' => 'none',
        ],

        'member.remove_liaison' => [
            'area' => 'membership',
            'change_type' => 'remove_liaison',
            'label' => 'Remove liaison',
            'groups' => ['gcep', 'vcep', 'scvcep'],
            'impact' => 'minor',
            'requires_approval' => false,
            'approval_step' => null,
            'approvers' => 'none',
        ],
    ],
];