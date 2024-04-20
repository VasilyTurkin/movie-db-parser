<?php

namespace Src;

class Progress
{
    private const DUMP_FILE_PATH = __DIR__ . '/../data/progress.json';

    private array $progress;

    public function __construct()
    {
        $this->progress = [
            'lastSeen' => [
                'sourceId' => 1,
            ]
        ];

        if (file_exists(self::DUMP_FILE_PATH)) {
            $progress = json_decode(file_get_contents(self::DUMP_FILE_PATH), true);
            if (!empty($progress)) {
                $this->progress = $progress;
            }
        }
    }

    public function getLastSeenId(): int
    {
        return $this->progress['lastSeen']['sourceId'] ?? 1;
    }

    public function rememberLastSeenId(int $lastSeenId): void
    {
        $this->progress['lastSeen']['sourceId'] = $lastSeenId;

        file_put_contents(self::DUMP_FILE_PATH, json_encode($this->progress));
    }
}
