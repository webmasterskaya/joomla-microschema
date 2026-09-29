<?php

namespace Joomla\Component\Microschema\Administrator\Schema;

final readonly class SchemaCandidate
{
    public string $uid;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $uid, public array $data, public int $priority = 0)
    {
        $uid = trim($uid);

        if ($uid === '') {
            throw new \InvalidArgumentException('The schema candidate UID must not be empty.');
        }

        $this->uid = $uid;
    }
}
