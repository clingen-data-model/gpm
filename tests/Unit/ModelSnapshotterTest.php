<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Service\ModelSnapshotter;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\GroupType;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Models\GroupStatus;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\ExpertPanelType;

class ModelSnapshotterTest extends TestCase
{
    private $snapshotter, $groupAttr, $model;

    public function setup():void
    {
        $this->snapshotter = new ModelSnapshotter();
        $this->groupAttr = [
            'name'=>'Beans', 
            'group_type_id' => 1,
            'group_status_id' => 1,
        ];
        $this->model = new Group($this->groupAttr);
    }
    

    /**
     * @test
     */
    public function creates_snapshot_of_model_with_no_relations_loaded()
    {
        $snapshot = $this->snapshotter->createSnapshot($this->model);

        $expected = [
            'class' => get_class($this->model),
            'attributes' => $this->groupAttr,
            'relations' => []
        ];

        $this->assertEquals($expected, $snapshot);        
    }

    /**
     * @test
     */
    public function creates_snapshot_of_model_with_scalar_relations()
    {
        $typeAttr = [ 'name' => 'Monkey' ];
        $this->model->setRelation('type', new GroupType($typeAttr));

        $statusAttr = [ 'name' => 'Beer' ];
        $this->model->setRelation('status', new GroupStatus($statusAttr));

        $snapshot = $this->snapshotter->createSnapshot($this->model);

        $expected = [
            'class' => get_class($this->model),
            'attributes' => $this->groupAttr,
            'relations' => [
                'type' => ['class' => GroupType::class, 'attributes' => $typeAttr, 'relations' => []],
                'status' => ['class' => GroupStatus::class, 'attributes' => $statusAttr, 'relations' => []],
            ],
        ];

        $this->assertEquals($expected, $snapshot);        
    }
    
    /**
     * @test
     */
    public function can_create_snapshot_for_model_with_nonscalar_relation()
    {
        $memberAttrs = [
            ['person_id' => 1, 'group_id' => 1],
            ['person_id' => 2, 'group_id' => 1],
        ];

        $members = collect($memberAttrs)->map(fn($m) => new GroupMember($m));
        $this->model->setRelation('members', $members);

        $snapshot = $this->snapshotter->createSnapshot($this->model);

        $expected = [
            'class' => get_class($this->model),
            'attributes' => $this->groupAttr,
            'relations' => [
                'members' => collect($memberAttrs)
                                ->map(function ($ma) {
                                    return [
                                        'class' => GroupMember::class,
                                        'attributes' => $ma,
                                        'relations' => []
                                    ];
                                })->toArray(),
            ],
        ];

        $this->assertEquals($expected, $snapshot);
    }

    /**
     * @test
     */
    public function can_create_snapshot_with_null_relations()
    {
        $this->model->setRelation('type', null);

        $snapshot = $this->snapshotter->createSnapshot($this->model);

        $this->assertEquals(['type' => null], $snapshot['relations']);
    }
    

    /**
     * @test
     */
    public function can_create_snapshot_with_nested_relations()
    {
        $epAttr = ['long_base_name' => 'Long VCEP Name'];
        $epModel = new ExpertPanel($epAttr);
        
        $epTypeAttr = ['name' => 'VCEP'];
        $epModel->setRelation('type', new ExpertPanelType($epTypeAttr));

        $this->model->setRelation('expertPanel', $epModel);

        $snapshot = $this->snapshotter->createSnapshot($this->model);

        $expected = [
            'class' => get_class($this->model),
            'attributes' => $this->groupAttr,
            'relations' => [
                'expertPanel' => [
                    'class' => ExpertPanel::class, 
                    'attributes' => $epAttr,
                    'relations' => [
                        'type' => [
                            'class' => ExpertPanelType::class,
                            'attributes' => $epTypeAttr,
                            'relations' => []
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $snapshot);
    }
    
    /**
     * @test
     */
    public function can_init_model_with_nested_relations_from_snapshot()
    {
        $snapshot = [
            'class' => get_class($this->model),
            'attributes' => array_merge($this->groupAttr, ['id' => 1]),
            'relations' => [
                'type' => [
                    'class' => GroupType::class,
                    'attributes' => ['name' => 'VCEP', 'id' => 4],
                    'relations' => []
                ],
                'expertPanel' => [
                    'class' => ExpertPanel::class, 
                    'attributes' => ['long_base_name' => 'Long VCEP Name', 'id' => 1],
                    'relations' => [
                        'type' => [
                            'class' => ExpertPanelType::class,
                            'attributes' => ['name' => 'VCEP', 'id' => 2],
                            'relations' => []
                        ]
                    ]
                ],
                'members' => [
                    [
                        'class'=>GroupMember::class,
                        'attributes' => ['person_id' => 1, 'group_id' => 1],
                        'relations' => [
                            'person' => [
                                'class' => Person::class,
                                'attributes' => ['first_name' => 'Dale', 'last_name' => 'Cooper', 'id' => 1]
                            ]
                        ]
                    ],
                    [
                        'class'=>GroupMember::class,
                        'attributes' => ['person_id' => 2, 'group_id' => 1],
                        'relations' => [
                            'person' => [
                                'class' => Person::class,
                                'attributes' => ['first_name' => 'Dougie', 'last_name' => 'Jones', 'id' => 2]
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $model = $this->snapshotter->initModelFromSnapshot($snapshot);

        $this->assertEquals(Group::class, get_class($model));

        $this->assertEquals(GroupType::class, get_class($model->type));
        $this->assertEquals(4, $model->type->id);

        $this->assertEquals(ExpertPanel::class, get_class($model->expertPanel));
        $this->assertEquals('Long VCEP Name', $model->expertPanel->long_base_name);

        $this->assertEquals(2, $model->members->count());
    }
    
}
