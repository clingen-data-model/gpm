<?php

namespace App\Modules\ExpertPanel;

class CoiData
{
    public $data;

    public function __construct(array|object $data)
    {
        $this->validateData();
        if (is_object($data)) {
            $this->data = $data;
        }
        if (is_array($data)) {
            $this->data = (object)$data;
        }
    }

    public function toJson(): string
    {
        return json_encode($this->data);
    }
    

    public function __get($key)
    {
        if (isset($this->data->{$key})) {
            return $this->data->{$key};
        }
        return null;
    }

    public function __set($key, $value)
    {
        $this->data->{$key} = $value;
    }

    private function validateData(): void
    {
        //code
    }
}
