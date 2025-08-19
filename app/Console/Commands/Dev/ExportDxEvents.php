<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class ExportDxEvents extends Command
{
    protected $signature = 'dx:export-events 
        {--path= : Output CSV path (default: storage/app/dx_events_catalog.csv)} 
        {--observed= : Observed event types CSV path (default: storage/app/dx_observed_event_types.csv)} 
        {--md= : Markdown output path (default: storage/app/dx_events_catalog.md)}
        {--include-abstract : Include abstract classes in the catalog (off by default)}';

    protected $description = 'Export a CSV + Markdown catalog of events (Class, Parent, Recordable, Publishable, Topic guess, Default event_type, Notes, SampleMessage) and observed DX event types.';

    // FQCNs used for classification
    private const RECORDABLE_BASE     = 'App\\Events\\RecordableEvent';
    private const PUBLISHABLE_IFACE   = 'App\\Events\\PublishableEvent';
    private const GROUP_EVENT_BASE    = 'App\\Modules\\Group\\Events\\GroupEvent';
    private const EXPERTPANEL_EVENT_BASE = 'App\\Modules\\ExpertPanel\\Events\\ExpertPanelEvent';
    private const PERSON_EVENT_BASE   = 'App\\Modules\\Person\\Events\\PersonEvent';
    private const PERSON_PUBLISH_TRAIT = 'App\\Modules\\Person\\Events\\Traits\\PublishesEvent';
    private const PUBLISHABLE_APP_IFACE = 'App\\Modules\\Group\\Events\\PublishableApplicationEvent';

    // Heuristics for notes/hints
    private const HINTS = [
        'App\\Modules\\Group\\Events\\GenesAdded' => 'Adds data.genes[] with {hgnc_id,gene_symbol,(mondo_id?,disease_name?,disease_entity?)}',
        'App\\Modules\\Group\\Events\\GeneRemoved' => 'Adds data.genes[] with {hgnc_id,gene_symbol,(mondo_id?,disease_name?,disease_entity?)}',
        'App\\Modules\\Group\\Events\\GroupMemberEvent' => 'Adds data.members[] with {id,first_name,last_name,email,group_roles[],additional_permissions[]}',
        'App\\Modules\\Group\\Events\\MemberAdded' => 'Adds data.members[]',
        'App\\Modules\\Group\\Events\\MemberRemoved' => 'Adds data.members[]',
        'App\\Modules\\Group\\Events\\MemberRetired' => 'Adds data.members[]',
        'App\\Modules\\Group\\Events\\MemberUnretired' => 'Adds data.members[]',
        'App\\Modules\\Group\\Events\\MemberRoleAssigned' => 'Adds data.members[]',
        'App\\Modules\\Group\\Events\\MemberRoleRemoved' => 'Adds data.members[]',
        'App\\Modules\\Group\\Events\\MemberPermissionsGranted' => 'Adds data.members[]',
        'App\\Modules\\Group\\Events\\MemberPermissionRevoked' => 'Adds data.members[]',
        'App\\Modules\\ExpertPanel\\Events\\StepApproved' => 'Step 1 includes data.members[] and data.scope{statement,genes[]}; Steps 2–4 base group only',
        'App\\Modules\\Group\\Events\\ExpertPanelAffiliationIdUpdated' => 'Base group only (affiliation_id reflected in group snapshot)',
        'App\\Modules\\Group\\Events\\ExpertPanelNameUpdated' => 'Base group only (name changes reflected in group snapshot)',
        'App\\Modules\\Group\\Events\\ScopeDescriptionUpdated' => 'Base group only (scope_description)',
        'App\\Modules\\Group\\Events\\GroupDescriptionUpdated' => 'Base group only (description)',
        'App\\Modules\\Group\\Events\\GroupStatusUpdated' => 'Base group only (status)',
        'App\\Modules\\Group\\Events\\ParentUpdated' => 'Base group only (parent_group)',
        'App\\Modules\\ExpertPanel\\Events\\CoiCompleted' => 'Base group only',
        'App\\Modules\\Person\\Events\\PersonCreated' => 'Topic person: data.person = $person->getAttributes()',
        'App\\Modules\\Person\\Events\\PersonDeleted' => 'Topic person: data.person = {}',
        'App\\Modules\\Person\\Events\\ProfileUpdated' => 'Topic person: data.person = payload data',
    ];

    // Known custom/overridden event_type strings
    private const CUSTOM_EVENT_TYPES = [
        'App\\Modules\\ExpertPanel\\Events\\StepApproved' => 'ep_definition_approved|vcep_draft_specifications_approved|vcep_pilot_approved|ep_final_approval',
        'App\\Modules\\Group\\Events\\ExpertPanelAffiliationIdUpdated' => 'ep_info_updated',
        'App\\Modules\\Group\\Events\\ExpertPanelNameUpdated' => 'ep_info_updated',
        // Class name plural → default would be genes_added, but DB historically has gene_added
        'App\\Modules\\Group\\Events\\GenesAdded' => 'genes_added (DB observed: gene_added)',
    ];

    public function handle(): int
    {
        $outPath         = $this->option('path') ?: storage_path('app/dx_events_catalog.csv');
        $obsPath         = $this->option('observed') ?: storage_path('app/dx_observed_event_types.csv');
        $mdPath          = $this->option('md') ?: storage_path('app/dx_events_catalog.md');
        $includeAbstract = (bool)$this->option('include-abstract');

        // Load observed stats + sample messages (portable table check via Schema::hasTable)
        $obsRows = [];
        $samplesByType = [];
        $samplesByTopicType = [];
        [
            'obsRows' => $obsRows,
            'samplesByType' => $samplesByType,
            'samplesByTopicType' => $samplesByTopicType,
        ] = $this->loadObserved();

        // Discover classes
        $classes = $this->discoverClasses(app_path());

        // Build catalog rows
        $catalogRows = [];
        $catalogHeader = ['Class', 'Parent', 'Recordable', 'Publishable', 'TopicGuess', 'DefaultEventType', 'OverridesEventType', 'Notes', 'SampleMessage'];
        $catalogRows[] = $catalogHeader;

        foreach ($classes as $fqcn) {
            // Skip non-Events BEFORE autoloading to avoid loading unrelated classes
            if (strpos($fqcn, '\\Events\\') === false) {
                continue;
            }

            if (!class_exists($fqcn)) {
                continue;
            }

            try {
                $ref = new ReflectionClass($fqcn);

                if ($ref->isInterface() || $ref->isTrait()) continue;
                if ($ref->isAbstract() && !$includeAbstract) continue;

                $parent        = $ref->getParentClass() ? $ref->getParentClass()->getName() : '';
                $isRecordable  = $this->isSubclassOf($ref, self::RECORDABLE_BASE);
                $isPublishable = $this->implementsInterface($ref, self::PUBLISHABLE_IFACE);
                $topicGuess    = $this->guessTopic($ref);

                $short        = $ref->getShortName();
                $defaultType  = array_key_exists($fqcn, self::CUSTOM_EVENT_TYPES)
                    ? self::CUSTOM_EVENT_TYPES[$fqcn]
                    : Str::snake($short);
                $overrides    = $this->declaresMethod($ref, 'getEventType') ? 'yes' : 'no';
                $notes        = self::HINTS[$fqcn] ?? '';

                // Try to attach a real observed sample message:
                // 1) exact topic+event_type
                // 2) fallback by event_type (topic-agnostic)
                $sample = '';
                if ($isPublishable) {
                    $key = "{$topicGuess}|{$defaultType}";
                    $sample = $samplesByTopicType[$key] ?? $samplesByType[$defaultType] ?? '';
                    $sample = $this->truncate($this->minifyJson($sample), 220);
                }

                $catalogRows[] = [
                    $fqcn,
                    $parent,
                    $isRecordable ? 'yes' : 'no',
                    $isPublishable ? 'yes' : 'no',
                    $topicGuess,
                    $defaultType,
                    $overrides,
                    $notes,
                    $sample,
                ];
            } catch (\Throwable $e) {
                $catalogRows[] = [$fqcn, 'ERROR: ' . $e->getMessage(), '', '', '', '', '', '', ''];
            }
        }

        // Write CSV
        $this->writeCsv($outPath, $catalogRows);
        $this->info("Catalog written: {$outPath}");

        // Observed types (from DB) — using Schema::hasTable internally
        if (!empty($obsRows)) {
            $obsHeader = ['Topic', 'EventType', 'Count', 'LastSent'];
            $this->writeCsv($obsPath, array_merge([$obsHeader], $obsRows));
            $this->info("Observed types written: {$obsPath}");
        } else {
            $this->warn('stream_messages table not found or empty; skipping observed export.');
        }

        // Markdown doc
        $this->writeMarkdownDoc(
            $mdPath,
            $catalogHeader,
            array_slice($catalogRows, 1),
            ['Topic', 'EventType', 'Count', 'LastSent'],
            $obsRows
        );
        $this->info("Markdown written: {$mdPath}");

        return self::SUCCESS;
    }

    /**
     * Load observed (topic,event_type) counts and capture one sample message per pair.
     *
     * @return array{obsRows: array<int, array{0:string,1:string,2:int,3:string}>, samplesByType: array<string,string>, samplesByTopicType: array<string,string>}
     */
    private function loadObserved(): array
    {
        $obsRows = [];
        $samplesByType = [];      // key: event_type
        $samplesByTopicType = []; // key: topic|event_type

        if (!Schema::hasTable('stream_messages')) {
            return compact('obsRows', 'samplesByType', 'samplesByTopicType');
        }

        // Observed stats
        $observed = DB::table('stream_messages')
            ->selectRaw("topic, JSON_UNQUOTE(JSON_EXTRACT(message, '$.event_type')) AS event_type, COUNT(*) AS cnt, MAX(created_at) AS last_sent")
            ->groupBy('topic', 'event_type')
            ->orderBy('topic')
            ->orderBy('event_type')
            ->get();

        foreach ($observed as $row) {
            $obsRows[] = [$row->topic, $row->event_type, (int)$row->cnt, (string)$row->last_sent];
        }

        // One sample (most recent) per topic+event_type
        $rows = DB::table('stream_messages')
            ->selectRaw("topic, JSON_UNQUOTE(JSON_EXTRACT(message, '$.event_type')) AS event_type, message, created_at")
            ->whereNotNull(DB::raw("JSON_EXTRACT(message, '$.event_type')"))
            ->orderBy('topic')
            ->orderBy('event_type')
            ->orderByDesc('created_at')
            ->get();

        $seen = [];
        foreach ($rows as $r) {
            $key = "{$r->topic}|{$r->event_type}";
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $msg = $this->minifyJson($r->message);
                $samplesByTopicType[$key] = $msg;
                // Also keep a topic-agnostic sample per event_type
                $samplesByType[$r->event_type] = $samplesByType[$r->event_type] ?? $msg;
            }
        }

        return compact('obsRows', 'samplesByType', 'samplesByTopicType');
    }

    private function discoverClasses(string $baseDir): array
    {
        $classes = [];
        $files = File::allFiles($baseDir);
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;
            $fqcn = $this->fqcnFromFile($file->getPathname());
            if ($fqcn) $classes[] = $fqcn;
        }
        return $classes;
    }

    private function fqcnFromFile(string $path): ?string
    {
        $src = file_get_contents($path);
        if ($src === false) return null;

        $ns = null; $class = null;
        $tokens = token_get_all($src);
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                $ns = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NAME_QUALIFIED)) {
                        $ns .= $tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    } elseif (is_array($tokens[$j]) && $tokens[$j][0] === T_NS_SEPARATOR) {
                        $ns .= '\\';
                    }
                }
            }
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '{') break;
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }
        return $class ? ($ns ? ($ns . '\\' . $class) : $class) : null;
    }

    private function isSubclassOf(ReflectionClass $ref, string $parentFqcn): bool
    {
        return $ref->isSubclassOf($parentFqcn);
    }

    private function implementsInterface(ReflectionClass $ref, string $ifaceFqcn): bool
    {
        foreach ($ref->getInterfaceNames() as $iface) {
            if ($iface === $ifaceFqcn) return true;
        }
        return false;
    }

    private function usesTraitRecursive(ReflectionClass $ref, string $traitFqcn): bool
    {
        while ($ref) {
            if (in_array($traitFqcn, $ref->getTraitNames(), true)) return true;
            $ref = $ref->getParentClass();
        }
        return false;
    }

    private function guessTopic(ReflectionClass $ref): string
    {
        if ($ref->isSubclassOf(self::PERSON_EVENT_BASE) || $this->usesTraitRecursive($ref, self::PERSON_PUBLISH_TRAIT)) {
            return config('dx.topics.outgoing.gpm-person-events', 'gpm-person-events');
        }
        if (
            $ref->isSubclassOf(self::GROUP_EVENT_BASE)
            || $ref->isSubclassOf(self::EXPERTPANEL_EVENT_BASE)
            || in_array(self::PUBLISHABLE_APP_IFACE, $ref->getInterfaceNames(), true)
        ) {
            return config('dx.topics.outgoing.gpm-general-events', 'gpm-general-events');
        }
        return '';
    }

    private function declaresMethod(ReflectionClass $ref, string $method): bool
    {
        if (!$ref->hasMethod($method)) return false;
        $m = $ref->getMethod($method);
        return $m->getDeclaringClass()->getName() === $ref->getName();
    }

    private function writeCsv(string $path, array $rows): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $fh = fopen($path, 'w');
        foreach ($rows as $row) fputcsv($fh, $row);
        fclose($fh);
    }

    private function writeMarkdownDoc(string $path, array $catalogHeader, array $catalogRows, array $obsHeader, array $obsRows): void
    {
        $topics = config('dx.topics.outgoing');
        $schemaVersions = config('dx.schema_versions');

        $total       = count($catalogRows);
        $publishable = collect($catalogRows)->where(3, 'yes')->count();
        $recordable  = collect($catalogRows)->where(2, 'yes')->count();

        $md  = "# Data Exchange Events Catalog\n\n";
        $md .= "_Generated: " . now()->toDateTimeString() . "_\n\n";
        $md .= "## Topics\n\n";
        foreach ($topics as $key => $topic) {
            $sv = $schemaVersions[$topic] ?? ($schemaVersions['gpm-general-events'] ?? 'n/a');
            $md .= "- **`{$topic}`** (alias: {$key}) — schema_version: `{$sv}`\n";
        }
        $md .= "\n---\n\n";
        $md .= "## Summary\n\n";
        $md .= "- Total event classes scanned: **{$total}**\n";
        $md .= "- Recordable: **{$recordable}**\n";
        $md .= "- Publishable: **{$publishable}**\n\n";

        if (!empty($obsRows)) {
            $md .= "## Observed Event Types (from `stream_messages`)\n\n";
            $md .= $this->renderMarkdownTable($obsHeader, $obsRows) . "\n";
        }

        $md .= "## Event Classes (from code)\n\n";
        $md .= $this->renderMarkdownTable($catalogHeader, $catalogRows) . "\n";

        $md .= "> Notes:\n";
        $md .= "> - `TopicGuess` is inferred from base classes/traits and config.\n";
        $md .= "> - `DefaultEventType` comes from snake-cased class name unless the class overrides `getEventType()`.\n";
        $md .= "> - If DB-observed event types differ (e.g., `GenesAdded` → `gene_added`), treat **DB as source of truth** for consumers.\n";

        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($path, $md);
    }

    private function renderMarkdownTable(array $header, array $rows): string
    {
        $out = '|' . implode('|', array_map([$this, 'escapeMd'], $header)) . "|\n";
        $out .= '|' . implode('|', array_fill(0, count($header), '---')) . "|\n";
        foreach ($rows as $row) {
            $cells = array_map(function ($v) { return $this->escapeMd((string)$v); }, $row);
            $out .= '|' . implode('|', $cells) . "|\n";
        }
        return $out;
    }

    private function escapeMd(string $s): string
    {
        $s = str_replace(["\r", "\n"], ' ', $s);
        return str_replace('|', '\\|', $s);
    }

    private function minifyJson(?string $json): string
    {
        if (!$json) return '';
        try {
            $arr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return json_encode($arr, JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return $json; // if it isn't valid JSON, return raw
        }
    }

    private function truncate(string $s, int $max = 220): string
    {
        return mb_strlen($s) > $max ? (mb_substr($s, 0, $max - 1) . '…') : $s;
    }
}
