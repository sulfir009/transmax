<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\Client;
use App\Services\BonusService;
use App\Services\Payments\MonobankWebhookHandler;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('monobank:test-webhook {invoiceId?} {--status=success} {--orderId=} {--uniqid=}', function () {
    if (app()->environment('production')) {
        $this->error('This command is disabled in production.');
        return;
    }

    $invoiceId = (string) ($this->argument('invoiceId') ?? '');
    $orderId = $this->option('orderId');
    $uniqid = $this->option('uniqid');
    $status = (string) ($this->option('status') ?? 'success');

    $order = null;
    if ($orderId) {
        $order = Order::find($orderId);
    }

    if (!$order && $uniqid) {
        $order = Order::where('uniqId', (string) $uniqid)
            ->orWhere('uniqid', (string) $uniqid)
            ->first();
    }

    if (!$order && $invoiceId !== '') {
        $order = Order::where('mono_invoice_id', $invoiceId)->first();
    }

    if (!$order) {
        $this->error('Order not found. Provide --orderId, --uniqid, or invoiceId argument.');
        return;
    }

    if ($invoiceId === '') {
        $invoiceId = (string) ($order->mono_invoice_id ?: ('TEST_INV_' . Str::uuid()));
    }

    if (!$order->mono_invoice_id) {
        $order->mono_invoice_id = $invoiceId;
        $order->mono_status = 'created';
        $order->save();
    }

    $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));
    $correlationId = PaymentFinalizer::buildCorrelationId($order->id, $legacyOrderId, $invoiceId);

    $payload = [
        'invoiceId' => $invoiceId,
        'status' => $status,
        'reference' => $legacyOrderId,
        'merchantPaymInfo' => [
            'reference' => $legacyOrderId,
        ],
    ];

    /** @var MonobankWebhookHandler $handler */
    $handler = app(MonobankWebhookHandler::class);
    $result = $handler->process($payload, 'monobank_cli', [
        'correlation_id' => $correlationId,
    ]);

    $this->info('Webhook simulation completed.');
    $this->line('Correlation ID: ' . $correlationId);
    $this->line('Result: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
})->purpose('Simulate Monobank webhook handling (non-production only).');


Artisan::command('bonuses:grant-initial {--dry-run} {--chunk=1000}', function () {
    $chunk = (int) $this->option('chunk') ?: 1000;
    $dryRun = (bool) $this->option('dry-run');

    $clientsTable = env('DB_PREFIX', 'mt') . '_clients';
    $ordersTable = env('DB_PREFIX', 'mt') . '_orders';

    $bonusService = app(BonusService::class);
    $amountCents = 10000;

    $processed = 0;
    $skipped = 0;
    $granted = 0;

    DB::table($clientsTable)
        ->join($ordersTable, $ordersTable . '.client_id', '=', $clientsTable . '.id')
        ->where($ordersTable . '.payment_status', 2)
        ->where($ordersTable . '.client_id', '>', 0)
        ->select($clientsTable . '.id')
        ->distinct()
        ->orderBy($clientsTable . '.id')
        ->chunk($chunk, function ($rows) use (
            $dryRun,
            $bonusService,
            $amountCents,
            &$processed,
            &$skipped,
            &$granted
        ) {
            foreach ($rows as $row) {
                $clientId = (int)($row->id ?? 0);
                if ($clientId <= 0) {
                    continue;
                }

                $processed++;

                $alreadyGranted = $bonusService->hasTransaction($clientId, 'grant_initial');

                if ($alreadyGranted) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $granted++;
                    continue;
                }

                $client = Client::find($clientId);
                if (!$client) {
                    $skipped++;
                    continue;
                }

                $bonusService->credit($client, $amountCents, 'grant_initial', [
                    'note' => 'initial campaign',
                ]);

                $granted++;
            }
        });

    $this->info('Initial bonus grant complete.');
    $this->line('Processed: ' . $processed);
    $this->line('Granted: ' . $granted);
    $this->line('Skipped (already granted or missing): ' . $skipped);
})->purpose('Grant initial бонусы клиентам с оплаченной историей.');


Artisan::command('dict:sync-from-file {file=/tmp/all_translation_codes.txt}
    {--table=mt_dictionary : Dictionary table name}
    {--section=1 : section_id}
    {--edit_by=1 : edit_by_user}
    {--chunk=500 : insert chunk size}
    {--dry-run : do not write to DB}', function () {

    $file  = (string) $this->argument('file');
    $table = (string) $this->option('table');

    if (!is_file($file)) {
        $this->error("File not found: {$file}");
        return 1;
    }

    if (!Schema::hasTable($table)) {
        $this->error("Table not found: {$table}");
        return 1;
    }

    $codes = array_values(array_unique(array_filter(array_map('trim', file($file)))));
    $codes = array_values(array_filter($codes, fn($c) => $c !== ''));

    $this->info('Total codes in file: ' . count($codes));

    // Существующие коды из БД
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
        return 0;
    }

    $dry       = (bool) $this->option('dry-run');
    $chunkSize = (int)  $this->option('chunk');
    $sectionId = (int)  $this->option('section');
    $editBy    = (int)  $this->option('edit_by');

    // Какие колонки реально есть
    $hasSection  = Schema::hasColumn($table, 'section_id');
    $hasEditBy   = Schema::hasColumn($table, 'edit_by_user');
    $hasComments = Schema::hasColumn($table, 'comments');

    $hasRU = Schema::hasColumn($table, 'title_ru');
    $hasUK = Schema::hasColumn($table, 'title_uk');
    $hasEN = Schema::hasColumn($table, 'title_en');

    if (!$hasRU || !$hasUK || !$hasEN) {
        $this->error("Table {$table} must have title_ru/title_uk/title_en columns. Aborting.");
        return 1;
    }

    $this->line('Columns: ' . json_encode([
        'section_id'   => $hasSection,
        'edit_by_user' => $hasEditBy,
        'comments'     => $hasComments,
        'title_ru'     => $hasRU,
        'title_uk'     => $hasUK,
        'title_en'     => $hasEN,
    ], JSON_UNESCAPED_UNICODE));

    $inserted = 0;

    foreach (array_chunk($missing, $chunkSize) as $chunk) {
        $rows = [];

        foreach ($chunk as $code) {
            $row = [
                'code'     => $code,
                'title_ru' => $code,
                'title_uk' => $code,
                'title_en' => $code,
            ];

            if ($hasSection)  $row['section_id']   = $sectionId;
            if ($hasEditBy)   $row['edit_by_user'] = $editBy;
            if ($hasComments) $row['comments']     = '';

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
    return 0;

})->purpose('Insert missing translation codes into mt_dictionary from a file');

Artisan::command('dict:fill-empty
    {--table=mt_dictionary : Dictionary table name}
    {--only= : ru|uk|en or empty for all}
    {--dry-run : do not write to DB}', function () {

    $table = (string) $this->option('table');
    $only  = (string) $this->option('only');
    $dry   = (bool) $this->option('dry-run');

    if (!Schema::hasTable($table)) {
        $this->error("Table not found: {$table}");
        return 1;
    }

    $cols = ['ru' => 'title_ru', 'uk' => 'title_uk', 'en' => 'title_en'];

    if ($only !== '' && !isset($cols[$only])) {
        $this->error("Invalid --only value. Use ru|uk|en or omit.");
        return 1;
    }

    $targets = ($only !== '') ? [$only] : ['ru','uk','en'];

    $totalUpdated = 0;

    foreach ($targets as $lang) {
        $col = $cols[$lang];

        if (!Schema::hasColumn($table, $col)) {
            $this->error("Column not found: {$table}.{$col}");
            return 1;
        }

        // Сколько строк пустые/NULL
        $count = DB::table($table)
            ->where(function($q) use ($col) {
                $q->whereNull($col)->orWhere($col, '=', '');
            })
            ->count();

        $this->info("{$col}: empty rows = {$count}");

        if ($count === 0) continue;

        if ($dry) {
            $this->warn("DRY RUN: would update {$count} rows for {$col}");
            continue;
        }

        // Обновляем только пустые: ставим = code
        // (через raw, чтобы было одним запросом)
        $updated = DB::table($table)
            ->where(function($q) use ($col) {
                $q->whereNull($col)->orWhere($col, '=', '');
            })
            ->update([$col => DB::raw('code')]);

        $totalUpdated += (int)$updated;
        $this->info("Updated {$updated} rows for {$col}");
    }

    $this->info($dry ? 'Dry-run complete.' : "Done. Updated total: {$totalUpdated}");
    return 0;

})->purpose('Fill empty title_ru/title_uk/title_en in mt_dictionary with code');

