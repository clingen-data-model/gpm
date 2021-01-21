<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Infrastructure\Service\MessageBusInterface;
use App\Domain\Application\Commands\InitiateApplication;

class ApplicationController extends Controller
{
    private $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
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
    public function store(Request $request)
    {
        $requestParams = [];
        foreach ($request->all() as $key => $value) {
            $requestParams[Str::camel($key)] = $value;
        }
        $requestParams['aggregateId'] = Uuid::uuid4();
        $requestParams['dateInitiated'] = Carbon::parse($requestParams['dateInitiated']);

        $command = new InitiateApplication(...$requestParams);

        $this->messageBus->handle($command);

        

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
