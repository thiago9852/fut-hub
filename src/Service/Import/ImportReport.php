<?php

namespace App\Service\Import;

final class ImportReport
{
    public int $recordsProcessed = 0;
    public int $recordsCreated = 0;
    public int $recordsUpdated = 0;
    public int $recordsFailed = 0;

    /** @var list<string> */
    public array $errors = [];

    /** Status compatível com ImportLog::status. */
    public string $status = 'CONCLUIDA';

    public function addError(string $message): void
    {
        $this->recordsFailed++;
        $this->errors[] = $message;
    }
}
