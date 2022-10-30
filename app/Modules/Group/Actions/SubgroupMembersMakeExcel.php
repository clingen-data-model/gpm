<?php
namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use Box\Spout\Common\Entity\Style\Color;
use App\Modules\Group\Models\GroupMember;
use Box\Spout\Common\Entity\Style\Border;
use Lorisleiva\Actions\Concerns\AsController;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class SubgroupMembersMakeExcel {
    use AsController;

    private $memberEagerLoads = ['members', 'members.roles', 'members.permissions', 'members.person', 'members.person.institution', 'members.person.country', 'members.latestCoi'];

    public function handle(ActionRequest $request, Group $group)
    {
        $group->load('children', ...$this->memberEagerLoads);

        $filename = sys_get_temp_dir().'/'.$this->makeFileName($group);
        $writer  = $this->getXLSXWriter($filename);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('All Group & Subgroup members');

        $this->writeAllSheet($writer, $group);

        $this->writeGroupSheet($writer, $group);
        foreach ($group->children->sortBy('name') as $subgroup) {
            $this->writeGroupSheet($writer, $subgroup);
        }

        $writer->close();
        return response()->download($filename, $this->makeFileName($group), ['Content-Type' => 'application/xlsx']);
    }

    private function writeAllSheet($writer, $group)
    {
        $groupsById = $group->children->keyBy('id');
        $groupsById->put($group->id, $group);
        $members = $group->members->all();

        foreach($group->children as $subgroup) {
            $members[] = $subgroup->members;
        }
        $groupedMembers = collect($members)->flatten()->groupBy('person_id');

        $headerRow = WriterEntityFactory::createRowFromArray(['Name', 'Email', 'Institution', 'Memberships'], $this->getHeaderStyle());
        $writer->addRow($headerRow);

        $rows = $groupedMembers->map(function ($memberships) use ($groupsById) {
            $person = $memberships->first()->person;
            return WriterEntityFactory::createRowFromArray([
                $person->name,
                $person->email,
                ($person->institution) ? $person->institution->name : null,
                $memberships->map(function ($mem) use ($groupsById) {
                    return (isset($groupsById[$mem->group_id]))
                        ? $groupsById[$mem->group_id]->display_name
                        : null;
                })->filter()->join(', ')
            ], (new StyleBuilder())->setShouldWrapText(false)->build());
        })->all();

        $writer->addRows($rows);
    }



    private function writeGroupSheet($writer, $group) {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheetName = $group->name.' '.strtoupper($group->type->name);
        if (strlen($group->name) > 26) {
            $sheetName = substr($group->name, 0, 23).'... '.strtoupper($group->type->name);
        }
        $sheet->setName(preg_replace('/[\/\?\*\[\]\\\:]/', '', $sheetName));

        $headerRow = $this->getGroupHeaderRow();

        $noWrapStyle = (new StyleBuilder())->setShouldWrapText(false)->build();

        $memberRows = $group->members->map(function ($member) use ($noWrapStyle) {
            $row = $this->getGroupMemberRow($member);
            $row->setStyle($noWrapStyle);
            return $row;
        });
        $writer
            ->addRow(WriterEntityFactory::createRowFromArray([$group->name]))
            ->addRow(WriterEntityFactory::createRowFromArray([]))
            ->addRow($headerRow, $this->getHeaderStyle())
            ->addRows($memberRows->toArray());
    }

    private function getGroupMemberRow(GroupMember $member)
    {
        return WriterEntityFactory::createRowFromArray($this->truncateValues([
            $member->person->first_name,
            $member->person->last_name,
            $member->person->email,
            $member->start_date->format('Y-m-d'),
            ($member->latestCoi && $member->latestCoi->completed_at)
                ? $member->latestCoi->completed_at->format('Y-m-d')
                : null,
            $member->end_date ? $member->end_date->format('Y-m-d') : null,
            $member->is_contact ? 'Yes' : 'No',
            $member->roles->pluck('name')->join(",\n"),
            $member->person->expertiseAsString,
            $member->notes,
            $member->training_level_1 ? 'Yes' : 'No',
            $member->training_level_2 ? 'Yes' : 'No',
            ($member->person->institution) ? $member->person->institution->name : null,
            $member->person->credentialsAsString,
            $member->person->biography,
            $member->person->phone,
            $member->person->addressString,
            ($member->person->contry) ? $member->person->contry->name : null,
            $member->person->timezone,
       ]));
    }

    private function truncateValues(Array $array, int $max = 10000): array
    {
        return collect($array)->map(function ($val) use ($max) {
            if (!is_string($val)) {
                return $val;
            }

            return strlen($val) > $max ? substr($val, 0, $max-3).'...' : $val;

        })->toArray();
    }


    private function getGroupHeaderRow()
    {
        return WriterEntityFactory::createRowFromArray([
            'first_name',
            'last_name',
            'email',
            'added',
            'COI_last_completed',
            'retired',
            'receives_group_notifications',
            'roles',
            'expertise',
            'notes',
            'training_level_1',
            'training_level_2',
            'institution',
            'credentials',
            'biography',
            'phone',
            'address',
            'country',
            'timezone',
        ], $this->getHeaderStyle());
    }


    private function makeFileName(Group $group)
    {
        return preg_replace('/[\/\?\*\[\]\\\]/', '', $group->display_name).'-full-membership'.Carbon::now()->format('Y-m-d').'.xlsx';
    }

    private function getXLSXWriter($fileName)
    {
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($fileName);
        return $writer;
    }

    private function getHeaderStyle()
    {
        $border = (new BorderBuilder())->setBorderBottom(Color::BLACK, Border::WIDTH_THIN)->build();

        return (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(14)
            ->setBorder($border)
            ->build();
    }


}
