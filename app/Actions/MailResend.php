<?php
namespace App\Actions;

use App\Models\Email;
use App\Mail\UserDefinedMailable;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Notifications\ValueObjects\MailAttachment;

class MailResend
{
    use AsController;

    public function __construct(private MailSendUserDefined $sendUserDefinedMail)
    {
    }

    public function handle(
        $to,
        string $subject,
        string $body,
        ?array $attachments = [],
        ?array $ccAddresses = [],
        ?array $bccAddresses = [],
    ) {

        // TOMORROW: Need to convert $to, $ccAddresses, $bccAddresses into array of [["name"=>"beans mccradden", "address" => 'beans@farts.com']]
        $this->sendUserDefinedMail->handle(
            to: $to,
            subject: $subject,
            body: $body,
            attachments: $attachments,
            ccAddresses: $ccAddresses,
            bccAddresses: $bccAddresses
        );
    }

    public function asController(ActionRequest $request)
    {
        $attachments = collect($request->attachments)
                        ->map(function ($file) {
                            return MailAttachment::createFromUploadedFile($file);
                        })
                        ->toArray();

        $mailData = [
            'subject' => $request->subject,
            'body' => $request->body,
            'to' => $this->structureAddressArray($request->to),
            'ccAddresses' => $this->structureAddressArray($request->cc ?? []),
            'bccAddresses' => $this->structureAddressArray($request->bcc ?? []),
            'attachments' => $attachments,
        ];

        $this->handle(...$mailData);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('mail-log-view')
            || ($request->user()->person && $request->user()->person->isCoordinator);
    }

    public function rules()
    {
        return [
            'to' => 'required|array',
            // 'to.*.address' => 'required|email',
            'cc' => 'nullable|array',
            // 'cc.*.address' => 'required|email',
            'bcc' => 'nullable|array',
            // 'bcc.*.address' => 'required|email',
            'subject' => 'required',
            'body' => 'required'
        ];
    }

    private function structureAddressArray(array $addressArray): array
    {
        return array_map(
            function ($address, $name) {
                if (is_array($name)) {
                    return $name;
                }
                return ['address' => $address, 'name' => $name];
            },
            array_keys($addressArray),
            array_values($addressArray)
        );
    }
}
