<?php
namespace App\Services;

use App\Services\MondoLookupInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class MondoLookup implements MondoLookupInterface
{
    public function findNameByMondoId($mondoId): string
    {
        $diseaseData = DB::connection(config('database.gt_db_connection'))
                ->table('diseases')
                ->select('name')
                ->where('mondo_id', $mondoId)
                ->first();

        if (!$diseaseData) {
            throw new Exception('No disease with MONDO ID '.$mondoId.' in our records.');
        }

        return $diseaseData->name;
    }
}
