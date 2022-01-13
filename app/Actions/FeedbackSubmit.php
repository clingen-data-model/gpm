<?php

namespace App\Actions;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsJob;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Lorisleiva\Actions\Concerns\AsAction;

class FeedbackSubmit
{
    use AsAction;
    use AsJob;

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
        $issueFields = [
            "fields" => [
                "project" => ["key" => "GPMEP"],
                "issuetype" => ["name" => $type],
                'summary' => $summary,
                'description' => $description,
                'customfield_18050' => $user,
                'customfield_18049' => $url,
            ]
        ];

        if ($type == 'Bug') {
            $issueFields['fields']['customfield_18006'] = $severity;
        }


        try {
            $issueResponse = $this->http->request('post', 'https://broadinstitute.atlassian.net/rest/api/2/issue', [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode(config('app.jira.user').':'.config('app.jira.token')),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => $issueFields
            ]);
        } catch (RequestException $e) {
            return;
        }
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
        // return $this->handle(...$data);

        static::dispatch(...$data);

        return true;
    }

    public function rules()
    {
        return [
            'summary' => 'required',
            'description' => 'required',
            'url' => 'required',
            'type' => 'required|in:Bug,Task',
            'severity' => 'required_if:type,Bug',
        ];
    }
}
