# GPM ClinGen DataExchange Implementation

## Notes about the DX implementation
* All topics are on a single partition.

## Configuration
A number of environment variables can be set to configure DX authentication and topics. For details on configuration see the dx config file att[config/dx.php](/config/dx.php)

## Concepts
`MessageStream`: An object with consumes messages from a set of topics, yielding `DxMessage` DTOs as messages are consumed.

`DxMessage`: A data transfer object representing a DX message.

`MessageProcessor`: An action who's handle method takes a `DxMessage` an argument, does some processing, and returns the `DxMessage`.

## Consuming Topics
The GPM consumes all available fmessages from subscribed topics every hour.
Consuming topics from the data exchange can be done using `App\DataExchnage\Actions\DxConsume`:
```
    // or $consume = app()->make(DxConsume::class);
    $consume->handle(['topic_1', 'topic_2']);
```
The consumer adds topics to a `MessageStream` and passes consumed messages to a `MessageProcessor` which processes the messages.

By default the GPM binds `KafkaMessageStream` to the `MessageStream` contract, and `IncomingMessageProcess` to the `MessageProcessorContract`.

`IncomingMessageProcessor` delegates creation of a `StreamMessage` model based on the `DxMessage` to `IncomingMessageStore` and uses the `MessageHandlerFactory` to instantiate message specific processing.

## Actions taken on receipt of CSPEC messages
If the affiliation associated with the event does not exist, or has an incompatible application status a `DataSynchronizationException` is thrown, an error is logged and the message is left unprocessed.

<table>
    <tr>
        <th>Event Type</th>
        <th>Action Taken</th>
        <th>Action Class</th>
    </tr>
    <tr>
        <td>classified-rules-approved</td>
        <td>Marks Draft Rules step (2) approved.</td>
        <td>App\DataExchange\Actions\ClassifiedRulesApprovedProcessor</td>
    </tr>
    <tr>
        <td>pilot-rules-approved</td>
        <td>Marks Pilot Rules step (3) approved if not previously already approved.  If previously approved, assigns a task to review sustained curation responses.</td>
        <td>App\DataExchange\Actions\ClassifiedRulesApprovedProcessor</td>
    </tr>
</table>
