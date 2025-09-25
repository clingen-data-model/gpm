<?php
namespace App\Modules\ExpertPanel\Service;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class AffilsClient
{
    private string $base; private string $key; private array $paths;
    public function __construct() {
        $this->base = rtrim(config('services.affils.base_url'),'/');
        $this->key  = config('services.affils.api_key');
        $this->paths= config('services.affils.paths');
    }
    private function http() {
        return Http::timeout(config('services.affils.timeout',15))
            ->withHeaders(['x-api-key'=>$this->key])->acceptJson()->asJson();
    }
    public function create(array $payload) {
        return $this->http()->post($this->base.$this->paths['create'], $payload);
    }
    
    public function detail($uuid=null, $affID=null) {
        if (!$uuid && !$affID) {
            throw new \InvalidArgumentException('You must provide $uuid or $affID.');
        }
        
        if ($uuid) { // By UUID
            $base = rtrim($this->base . $this->paths['detail'], '/');
            $url  = $base . '/uuid/' . rawurlencode($uuid) . '/';
            $response = $this->http()->get($url);
            return $response;
        }

        // by Affiliation ID
        $url = $this->base . $this->paths['detail'] . '?affil_id=' . rawurlencode($affID);
        return $this->http()->get($url);
    }

    public function updateByEpID(string $epID, array $fields) {
        $prefix = rtrim($this->paths['update_by_epid'], '/');
        $url    = $this->base . $prefix . '/' . $epID . '/';

        return $this->http()->patch($url, $fields);
    }

    public function createCDWG(array $payload) {
        return $this->http()->post($this->base.$this->paths['cdwg_create'], $payload);        
    }

    public function updateCDWG(int $id, array $payload) {
        $url = $this->base . sprintf($this->paths['cdwg_update'], $id);
        return $this->http()->patch($url, $payload);        
    }
}
