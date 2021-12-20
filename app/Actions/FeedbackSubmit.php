<?php

namespace App\Actions;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use GuzzleHttp\Exception\GuzzleException;
use Lorisleiva\Actions\Concerns\AsController;

class FeedbackSubmit
{
    use AsController;

    const JIRA_CREATE_URI = 'https://broadinstitute.atlassian.net/rest/api/2/issue';
    const JIRA_BOARD_URI = 'https://broadinstitute.atlassian.net/rest/agile/1.0/board/828/issue';

    public function __construct(private ClientInterface $http)
    {
    }

    public function handle(
        $url,
        $user,
        $summary,
        $description,
        $type,
        $severity
    ) {
        $issueResponse = $this->http->request('post', 'https://broadinstitute.atlassian.net/rest/api/2/issue', [
            'headers' => [
                'Authorization' => 'Basic '.base64_encode(config('app.jira.user').':'.config('app.jira.token')),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => [
                "fields" => [
                    "project" => ["key" => "GPMEP"],
                    "issuetype" => ["name" => $type],
                    'summary' => $summary,
                    'description' => $description,
                    'customfield_18050' => $user,
                    'customfield_18049' => $url,
                    'customfield_18006' => ['id' => $severity]
                ]
            ]
        ]);

        $responseObj = json_decode($issueResponse->getBody()->getContents());

        $issueKey = $responseObj->key;

        $this->http->request('POST', self::JIRA_BOARD_URI, [
            'headers' => [
                'Authorization' => 'Basic '.base64_encode(config('app.jira.user').':'.config('app.jira.token')),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
             'json' => ['issues' => [$issueKey]]
        ]);

        return $responseObj;
    }

    public function asController(ActionRequest $request)
    {
        $data = $request->only('summary', 'description', 'url', 'type', 'severity');
        $data['user'] = $request->user()->name.'<'.$request->user()->email.'>';
        return $this->handle(...$data);
    }

    public function rules()
    {
        return [
            'summary' => 'required',
            'description' => 'required',
            'url' => 'required',
            'type' => 'required|in:Bug,Story',
            'severity' => [
                Rule::requiredIf(function () {
                    return request()->type == 'Bug';
                }),
                Rule::in(collect(config('feedback.severities'))->pluck('id')->toArray())
            ]
        ];
    }
}
