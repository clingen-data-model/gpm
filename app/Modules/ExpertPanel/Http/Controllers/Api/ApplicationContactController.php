<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Http\Requests\AddContactRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Jobs\ContactAdd;
use App\Modules\ExpertPanel\Jobs\RemoveContact;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Validation\ValidationException;
use App\Modules\ExpertPanel\Http\Requests\ApplicationContactRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Modules\ExpertPanel\Exceptions\PersonNotContactException;

class ApplicationContactController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function index($expertPanelUuid)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);

        return $expertPanel->contacts;
    }
}
