<?php

namespace App\Mail\UserDefinedMailTemplates;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\View;

Abstract Class AbstractUserDefinedMailTemplate
{
    public function __construct(protected Group $group)
    {
    }

    abstract public function getTemplate(): string;

    public function renderBody(): string
    {
        $view = View::make($this->getTemplate(), $this->getContext());
        
        return $view->render();
    }

    protected function getContext(): array
    {
        return ['group' => $this->group];
    }

    abstract public function renderSubject(): string;

    /**
     * Returns array of contacts w/ name, email, and uuid
     */
    public function getTo(): array
    {
        return $this->group->contacts
        ->pluck('person')
        ->map(function ($c) {
            return [
                'name' => $c->name,
                'email' => $c->email,
                'uuid' => $c->uuid
            ];
        })->toArray();
    }
    
    /**
     * Returns associative array of cc addresses with keys name, email
     */
    public function getCC(): array
    {
        return [];
    }

    public function getAttachments(): array
    {
        return [];
    }

}