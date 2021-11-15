<?php

namespace Database\Seeders;

use Ramsey\Uuid\Uuid;
use Database\Seeders\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Person\Models\Institution;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get institutions from https://clinicalgenome.org/data-pull/organizations/
        $institutions = json_decode(file_get_contents('https://clinicalgenome.org/data-pull/organizations/'));

        $items = array_map(function ($inst) {
            $countryId = $this->resolveCountryId($inst);

            return [
                'uuid' => Uuid::uuid4()->toString(),
                'name' => $this->resolveName($inst->title),
                'abbreviation' => $this->resolveAbbreviation($inst->title),
                'url' => $this->resolveUrl($inst),
                'address' => $this->resolveAddress($inst),
                'country_id' => $countryId,
                'website_id' => $inst->id,
            ];
        }, $institutions);


        $this->seedFromArray($items, Institution::class);
    }

    private function resolveCountryId($inst): int|null
    {
        if (is_null($inst->countries)) {
            return null;
        }

        if (count($inst->countries) > 1) {
            throw new \Exception("Multiple countries found for ".$inst->title.".  This is not currently supported.");
        }

        if (count($inst->countries) == 0) {
            return null;
        }
        
        $countryName = $inst->countries[0]->title;

        switch ($inst->countries[0]->id) {
            case 2578:
                return 226;
            case 2577:
                return 225;
            case 2461:
                return 115;
            default:
                $countryRow = DB::table('countries')
                                ->where('name', $countryName)
                                ->select('id')
                                ->first();

                if ($countryRow) {
                    return $countryRow->id;
                }

                return null;
        }
    }

    private function resolveName($titleString)
    {
        $split = $this->splitNameAndAbbreviation($titleString);
        return $split['name'];
    }

    private function resolveAbbreviation($titleString)
    {
        $split = $this->splitNameAndAbbreviation($titleString);
        return $split['abbr'];
    }

    private function splitNameAndAbbreviation($string)
    {
        $parts = explode(' (', $string);
        $name = $parts[0];
        $abbr = null;
        if (isset($parts[1])) {
            $abbr = substr($parts[1], 0, -1);
        }

        return [
            'name' => $name,
            'abbr' => $abbr
        ];
    }
    
    private function resolveUrl($inst)
    {
        if (!empty($inst->url_general)) {
            return $inst->url_general;
        }
        if (substr($inst->address, 0, 4) == 'http') {
            return $inst->address;
        }

        return null;
    }

    private function resolveAddress($inst)
    {
        if (substr($inst->address, 0, 4) == 'http') {
            return null;
        }
        return $inst->address;
    }

    public function seedFromArray($items, $modelClass)
    {
        Model::unguard();
        foreach ($items as $itemData) {
            $modelClass::updateOrCreate(['website_id'=>$itemData['website_id']], $itemData);
        }
        Model::reguard();
    }
}
