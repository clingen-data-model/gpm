<?php

namespace App\Actions;

use App\Models\ForeignComponentMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class ForeignComponentsScanMemberships
{
    use AsAction;

    public string $commandSignature = 'foreign-components:scan-memberships {--rebuild : Reconstruct in-scope people from all available membership history}';
    public string $commandDescription = 'Reconstruct Foreign Component membership stream state and unretirement dates.';

    public function handle(bool $rebuild = false, ?callable $output = null): array
    {
        $stats = ['people_scanned' => 0, 'messages_processed' => 0, 'dates_updated' => 0, 'no_history' => 0, 'mismatches' => 0, 'errors' => 0];
        $connection = DB::connection();
        $logging = $connection->logging();
        $connection->disableQueryLog();
        // A fixed upper bound keeps newly arriving messages for the next run.
        $upperId = (int) DB::table('stream_messages')->where('topic', 'gpm-general-events')->max('id');

        try {
            ForeignComponentMember::where('is_in_scope', true)->select('id')->chunkById(100, function ($rows) use ($rebuild, $upperId, $output, &$stats) {
                foreach ($rows as $row) {
                    try {
                        $result = DB::transaction(function () use ($row, $rebuild, $upperId) {
                            $member = ForeignComponentMember::whereKey($row->id)->lockForUpdate()->first();
                            if (!$member || !$member->is_in_scope) {
                                return null;
                            }
                            return $this->scanPerson($member, $upperId, $rebuild);
                        });
                        if ($result === null) {
                            continue;
                        }
                        $stats['people_scanned']++;
                        $stats['messages_processed'] += $result['processed'];
                        $stats['dates_updated'] += (int) $result['date_updated'];
                        $stats['no_history'] += (int) $result['no_history'];
                        if ($result['warning']) {
                            $stats['mismatches']++;
                            $this->warn($result['warning'], $output);
                        }
                    } catch (RuntimeException $exception) {
                        $stats['errors']++;
                        $this->warn('Foreign Component row '.$row->id.': '.$exception->getMessage(), $output);
                    }
                }
            });
        } finally {
            if ($logging) {
                $connection->enableQueryLog();
            }
        }

        return $stats;
    }

    private function scanPerson(ForeignComponentMember $member, int $upperId, bool $rebuild): array
    {
        $checkpoint = $rebuild ? null : $member->last_stream_message_id;
        $state = $checkpoint === null ? null : $member->stream_membership_state;
        if ($checkpoint !== null && (!is_array($state)
            || !is_array($state['active_group_uuids'] ?? null)
            || !is_bool($state['initial_activation_occurred'] ?? null)
            || !is_string($state['last_processed_event_date'] ?? null))) {
            throw new RuntimeException('Missing or invalid persisted state; run with --rebuild. Checkpoint unchanged.');
        }
        $active = array_fill_keys($state['active_group_uuids'] ?? [], true);
        $activated = $state['initial_activation_occurred'] ?? false;
        $lastDate = $state['last_processed_event_date'] ?? null;
        $unretired = $checkpoint === null ? null : $member->last_unretired_at?->format('Y-m-d H:i:s');
        $oldUnretired = $member->last_unretired_at?->format('Y-m-d H:i:s');
        $maxId = $checkpoint;
        $processed = 0;

        $query = DB::table('stream_messages')->select(['id', 'message', 'created_at'])
            ->where('topic', 'gpm-general-events')
            ->whereIn('message->event_type', ['member_added', 'member_retired', 'member_unretired'])
            ->where('id', '>', $checkpoint ?? 0)->where('id', '<=', $upperId);
        // Match UUID inside any member object, even when that object has additional fields.
        if (DB::connection()->getDriverName() === 'sqlite') {
            $query->whereRaw("EXISTS (SELECT 1 FROM json_each(stream_messages.message, '$.data.members') AS stream_member WHERE json_extract(stream_member.value, '$.uuid') = ? OR json_extract(stream_member.value, '$.id') = ?)", [$member->person_uuid, $member->person_uuid]);
        } else {
            $query->where(function ($query) use ($member) {
                $query->whereJsonContains('message->data->members', ['uuid' => $member->person_uuid])
                    ->orWhereJsonContains('message->data->members', ['id' => $member->person_uuid]);
            });
        }

        // Use the same effective date for ordering and processing, including legacy messages.
        $dateColumn = DB::connection()->getQueryGrammar()->wrap('message->date');
        foreach ($query->orderByRaw("COALESCE(NULLIF($dateColumn, 'null'), stream_messages.created_at)")->orderBy('id')->lazy(500) as $message) {
            $event = json_decode($message->message, true, 512, JSON_THROW_ON_ERROR);
            $date = $event['date'] ?? $message->created_at;
            $group = $event['data']['group']['uuid'] ?? $event['data']['group']['id'] ?? $event['data']['expert_panel']['id'] ?? null;
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $date)
                || !($parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date))
                || $parsed->format('Y-m-d H:i:s') !== $date || !is_string($group) || $group === '') {
                throw new RuntimeException('Invalid event date or group UUID at message '.$message->id.'. Checkpoint unchanged.');
            }
            if ($lastDate !== null && $date < $lastDate) {
                throw new RuntimeException('Late event '.$message->id.' predates processed history; run with --rebuild. Checkpoint unchanged.');
            }
            if ($event['event_type'] === 'member_retired') {
                unset($active[$group]);
            } else {
                if (!$active && $activated) {
                    $unretired = $date;
                }
                $active[$group] = true;
                $activated = true;
            }
            $lastDate = $date;
            $maxId = max($maxId ?? 0, $message->id);
            $processed++;
        }

        $groups = array_keys($active);
        sort($groups);
        $member->fill([
            'stream_membership_state' => [
                'active_group_uuids' => $groups,
                'initial_activation_occurred' => $activated,
                'last_processed_event_date' => $lastDate,
            ],
            'stream_membership_count' => count($groups),
            'last_stream_message_id' => $maxId,
            'last_unretired_at' => $unretired,
        ]);
        if ($member->isDirty()) {
            $member->save();
        }

        return [
            'processed' => $processed,
            'date_updated' => $oldUnretired !== $unretired,
            'no_history' => $maxId === null,
            'warning' => count($groups) !== $member->active_membership_count
                ? 'Person '.$member->person_uuid.': stream count '.count($groups).' differs from current GPM count '.$member->active_membership_count.'. Current report state preserved.'
                : null,
        ];
    }

    private function warn(string $message, ?callable $output): void
    {
        Log::warning($message);
        if ($output) {
            $output($message);
        }
    }

    public function asCommand(Command $command): int
    {
        $stats = $this->handle((bool) $command->option('rebuild'), fn ($warning) => $command->warn($warning));
        foreach ($stats as $label => $value) {
            $command->info(str_replace('_', ' ', ucfirst($label)).': '.$value);
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
