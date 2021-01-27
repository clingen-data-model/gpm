<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Carbon;
use App\Models\Cdwg;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Domain\Application\Jobs\InitiateApplication;
use App\Domain\Application\Models\Application;
use App\Http\Requests\InitiateApplicationRequest;
use App\Domain\Application\Jobs\AddContact;

class ApplicationController extends Controller
{
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(InitiateApplicationRequest $request)
    {
        $data = $request->except('contacts');
        $data['cdwg_id'] = (int)$request->cdwg_id;
        $data['date_initiated'] = $request->date_initiated ? Carbon::parse($request->date_initiated) : null;
        $job = new InitiateApplication(...$data);
        $this->dispatcher->dispatchNow($job);

        $application = Application::findByUuid($request->uuid);
        
        return $application;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }
}
