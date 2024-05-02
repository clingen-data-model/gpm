<?php

namespace App\Actions\DataFixes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsCommand;

class ReorderStreamMessages
{
    use AsCommand;

    public string $commandSignature = 'data-fix:reorder-stream-messages';

    public function handle()
    {
        try {
            $this->backupStreamMessages();
            $this->testBackupTable();
        } catch (\Exception $e) {
            Log::error('Error backing up stream messages. Error: ' . $e->getMessage());
            return;
        }

        $this->createTempTable();
        $this->loadTempTable();
        $this->truncateStreamMessages();
        
        try {
            $this->reloadStreamMessages();
        } catch (\Exception $e) {
            $this->restoreStreamMessages();
            Log::error('Error reloading stream messages. Original stream_message records restored. Error: ' . $e->getMessage());
        }
        
        $this->cleanup();

    }

    /**
     * Backup the stream messages table in case something goes wrong
     */
    private function backupStreamMessages(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `sm_backup` AS SELECT * FROM `stream_messages`';

        DB::statement($sql);
    }

    private function testBackupTable(): void
    {
        $backup_count_sql = 'SELECT count(*) as count from `sm_backup`';
        $original_count_sql = 'SELECT count(*) as count from `stream_messages`';

        $backup_count = DB::select($backup_count_sql)[0]->count;
        $original_count = DB::select($original_count_sql)[0]->count;

        if ($backup_count != $original_count) {
            throw new \Exception('Backup count does not match original count');
        }
    }
    
    
    /**
     * Create a temp table to hold the ordered stream messages
     */
    private function createTempTable()
    {
        $sql = <<<SQL
            CREATE TEMPORARY TABLE IF NOT EXISTS sm_temp (
                id int auto_increment Primary key, 
                topic varchar(127) not null,
                message json,
                sent_at datetime,
                error TEXT,
                created_at datetime,
                updated_at datetime,
                orig_id int
            )
        SQL;

        DB::statement($sql);
    }

    private function loadTempTable(): void
    {
        $sql = <<<SQL
            INSERT INTO sm_temp (topic, `message`, sent_at, error, created_at, updated_at, orig_id)
            SELECT topic, `message`, NULL, error, created_at, updated_at, id AS orig_id FROM stream_messages 
            order by created_at, orig_id
        SQL;

        $results = DB::statement($sql);

    }
    
    
    /**
     * Truncate the stream messages table
     */
    private function truncateStreamMessages(): void
    {
        DB::table('stream_messages')->truncate();
    }

    /**
     * Insert ordered stream messages from temp table
     **/
    private function reloadStreamMessages(): void
    {
        $sql = <<<SQL
            INSERT INTO stream_messages (id, topic, `message`, sent_at, error, created_at, updated_at)
            SELECT id, topic, `message`, sent_at, error, created_at, updated_at FROM sm_temp
        SQL;

        DB::statement($sql);
    }

    /**
     * Restore the stream messages table from the backup table
     */
    private function restoreStreamMessages(): void
    {
        $sql = <<<SQL
            INSERT INTO stream_messages (id, topic, `message`, sent_at, error, created_at, updated_at) 
            SELECT id, topic, `message`, sent_at, error, created_at, updated_at 
            FROM sm_backup
            ORDER BY created_at, orig_id
        SQL;

        DB::statement($sql);
    }
        
    /**
     * Clean up the backup table and temp table
     */
    private function cleanup(): void
    {
        # Drop the stream messages backup table
        DB::statement('drop table sm_backup');

        # Drop temp table
        DB::statement('drop table if exists sm_temp');
    }
}
