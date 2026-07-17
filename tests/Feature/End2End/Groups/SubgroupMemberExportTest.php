<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use OpenSpout\Reader\XLSX\Reader;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class SubgroupMemberExportTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        $this->group = $this->expertPanel->group;

        $this->activeMember = GroupMember::factory()
            ->create(['group_id' => $this->group->id])
            ->assignRole('coordinator');

        $this->retiredMember = GroupMember::factory()
            ->create(['group_id' => $this->group->id, 'end_date' => Carbon::parse('2024-01-15')]);

        $this->subgroup = Group::factory()->wg()->create(['parent_id' => $this->group->id]);
        $this->subgroupMember = GroupMember::factory()->create(['group_id' => $this->subgroup->id]);

        $this->user = $this->setupUser();
        Sanctum::actingAs($this->user);
    }

    private function url(): string
    {
        return '/api/report/groups/'.$this->group->uuid.'/subgroup-member-export';
    }

    #[Test]
    public function it_downloads_an_xlsx_workbook()
    {
        $response = $this->get($this->url());

        $response->assertStatus(200);
        $response->assertDownload();
        $this->assertStringContainsString(
            '.xlsx',
            $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
    }

    #[Test]
    public function the_workbook_is_a_valid_xlsx_with_member_data_across_sheets()
    {
        $response = $this->get($this->url());
        $response->assertStatus(200);

        $reader = new Reader();
        $reader->open($response->getFile()->getPathname());

        $sheetNames = [];
        $values = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetNames[] = $sheet->getName();
            foreach ($sheet->getRowIterator() as $row) {
                $values = array_merge($values, $row->toArray());
            }
        }
        $reader->close();

        // Summary sheet plus one sheet per group/subgroup.
        $this->assertContains('All Group & Subgroup members', $sheetNames);
        $this->assertCount(3, $sheetNames);

        // Members from both the parent group and the subgroup are exported.
        $this->assertContains($this->activeMember->person->email, $values);
        $this->assertContains($this->retiredMember->person->email, $values);
        $this->assertContains($this->subgroupMember->person->email, $values);
    }
}
