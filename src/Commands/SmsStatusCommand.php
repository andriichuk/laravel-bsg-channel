<?php

declare(strict_types=1);

namespace Andriichuk\BsgChannel\Commands;

use Andriichuk\BsgChannel\Services\SmsStatusService;
use Illuminate\Console\Command;

final class SmsStatusCommand extends Command
{
    protected $signature = 'bsg:sms-status
                            {identifier : BSG message ID or external reference}
                            {--reference : Look up by external reference instead of BSG message ID}';

    protected $description = 'Check the delivery status of a BSG SMS';

    public function handle(SmsStatusService $statuses): int
    {
        $identifier = $this->argument('identifier');

        $status = $this->option('reference')
            ? $statuses->byReference($identifier)
            : $statuses->byId($identifier);

        $rows = [];

        foreach ($status->toArray() as $field => $value) {
            $rows[] = [$field, $value ?? '-'];
        }

        $this->table(['Field', 'Value'], $rows);

        return self::SUCCESS;
    }
}
