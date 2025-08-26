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
    
    public function detail(string $uuid) {
        return $this->http()->get($this->base . $this->paths['detail'] . '?uuid=' . $uuid);
    }

    public function updateByEpID(string $epID, array $fields) {
        $prefix = rtrim($this->paths['update_by_epid'], '/');
        $url    = $this->base . $prefix . '/' . $epID . '/';

        return $this->http()->patch($url, $fields);
    }

    public function updateByUUID(string $UUID, array $fields) {
        $prefix = rtrim($this->paths['update_by_uuid'], '/');
        $url    = $this->base . $prefix . '/' . $UUID . '/';

        return $this->http()->patch($url, $fields);
    }
}
