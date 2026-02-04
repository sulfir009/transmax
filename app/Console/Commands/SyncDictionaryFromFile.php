<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncDictionaryFromFile extends Command
{
    protected $signature = 'dict:sync-from-file
                            {file=/tmp/all_translation_codes.txt : Path to file with codes (one per line)}
                            {--table=mt_dictionary : Dictionary table name}
                            {--section=1 : section_id}
                            {--edit_by=1 : edit_by_user}
                            {--chunk=500 : insert chunk size}
                            {--dry-run : do not write to DB}';

    protected $description = 'Sync mt_dictionary: insert missing translation codes from a file (one code per line).';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $table = (string) $this->option('table');

        if (!is_file($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        if (!Schema::hasTable($table)) {
            $this->error("Table not found: {$table}");
            return self::FAILURE;
        }

        $codes = array_values(array_unique(array_filter(array_map('trim', file($file)))));
        $codes = array_values(array_filter($codes, fn($c) => $c !== ''));

        $this->info('Total codes in file: ' . count($codes));

        // Берем существующие коды из БД
        $existing = DB::table($table)->pluck('code')->all();
        $existingMap = [];
        foreach ($existing as $c) {
            $existingMap[(string)$c] = true;
        }

        $missing = [];
        foreach ($codes as $c) {
            if (!isset($existingMap[$c])) {
                $missing[] = $c;
            }
        }

        $this->info('Existing in DB: ' . count($existingMap));
        $this->info('Missing to insert: ' . count($missing));

        if (count($missing) === 0) {
            $this->info('Nothing to insert.');
            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $sectionId = (int) $this->option('section');
        $editBy = (int) $this->option('edit_by');

        // Проверим какие колонки реально есть
        $hasSection = Schema::hasColumn($table, 'section_id');
        $hasEditBy  = Schema::hasColumn($table, 'edit_by_user');
        $hasComments = Schema::hasColumn($table, 'comments');
        $hasRU = Schema::hasColumn($table, 'title_ru');
        $hasUK = Schema::hasColumn($table, 'title_uk');
        $hasEN = Schema::hasColumn($table, 'title_en');

        if (!$hasRU || !$hasUK || !$hasEN) {
            $this->error("Table {$table} must have title_ru/title_uk/title_en columns. Aborting.");
            return self::FAILURE;
        }

        $this->line('Columns: ' . json_encode([
            'section_id' => $hasSection,
            'edit_by_user' => $hasEditBy,
            'comments' => $hasComments,
            'title_ru' => $hasRU,
            'title_uk' => $hasUK,
            'title_en' => $hasEN,
        ], JSON_UNESCAPED_UNICODE));

        $inserted = 0;

        foreach (array_chunk($missing, $chunkSize) as $chunk) {
            $rows = [];
            foreach ($chunk as $code) {
                $row = [
                    'code' => $code,
                    'title_ru' => $code,
                    'title_uk' => $code,
                    'title_en' => $code,
                ];

                if ($hasSection) $row['section_id'] = $sectionId;
                if ($hasEditBy) $row['edit_by_user'] = $editBy;
                if ($hasComments) $row['comments'] = '';

                $rows[] = $row;
            }

            if ($dry) {
                $this->warn('DRY RUN: would insert chunk size ' . count($rows));
                continue;
            }

            DB::table($table)->insert($rows);
            $inserted += count($rows);
            $this->info("Inserted: {$inserted}/" . count($missing));
        }

        $this->info($dry ? 'Dry-run complete.' : "Done. Inserted total: {$inserted}");

        return self::SUCCESS;
    }
}
