<?php

namespace App\Services\Calculation\Support;

final class TopKCandidateBuffer
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $itemsBySignature = [];

    /**
     * @var callable
     */
    private $isBetter;

    /**
     * @var callable
     */
    private $sorter;

    /**
     * @var array<string, int>
     */
    private array $stats = [
        'inserted' => 0,
        'replaced' => 0,
        'discarded' => 0,
        'deduped' => 0,
    ];

    public function __construct(
        private readonly int $capacity,
        callable $isBetter,
        callable $sorter,
    ) {
        $this->isBetter = $isBetter;
        $this->sorter = $sorter;
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function push(array $candidate): void
    {
        if ($this->capacity <= 0) {
            $this->stats['discarded']++;

            return;
        }

        $signature = isset($candidate['signature']) ? trim((string) $candidate['signature']) : '';
        if ($signature === '') {
            throw new \InvalidArgumentException('TopKCandidateBuffer candidate must include non-empty signature.');
        }

        if (isset($this->itemsBySignature[$signature])) {
            $existing = $this->itemsBySignature[$signature];
            if (($this->isBetter)($candidate, $existing)) {
                $this->itemsBySignature[$signature] = $candidate;
                $this->stats['replaced']++;
            } else {
                $this->stats['deduped']++;
            }

            return;
        }

        if (count($this->itemsBySignature) < $this->capacity) {
            $this->itemsBySignature[$signature] = $candidate;
            $this->stats['inserted']++;

            return;
        }

        $worstSignature = $this->findWorstSignature();
        if ($worstSignature === null) {
            $this->stats['discarded']++;

            return;
        }

        $worst = $this->itemsBySignature[$worstSignature] ?? null;
        if (!is_array($worst)) {
            $this->stats['discarded']++;

            return;
        }

        if (($this->isBetter)($candidate, $worst)) {
            unset($this->itemsBySignature[$worstSignature]);
            $this->itemsBySignature[$signature] = $candidate;
            $this->stats['replaced']++;

            return;
        }

        $this->stats['discarded']++;
    }

    public function count(): int
    {
        return count($this->itemsBySignature);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function worst(): ?array
    {
        $worstSignature = $this->findWorstSignature();
        if ($worstSignature === null) {
            return null;
        }

        $candidate = $this->itemsBySignature[$worstSignature] ?? null;

        return is_array($candidate) ? $candidate : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $items = array_values($this->itemsBySignature);
        usort($items, $this->sorter);

        return $items;
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return $this->stats + ['size' => $this->count(), 'capacity' => max(0, $this->capacity)];
    }

    private function findWorstSignature(): ?string
    {
        if (empty($this->itemsBySignature)) {
            return null;
        }

        $signatures = array_keys($this->itemsBySignature);
        $worstSignature = array_shift($signatures);
        if ($worstSignature === null) {
            return null;
        }

        foreach ($signatures as $signature) {
            $current = $this->itemsBySignature[$signature] ?? null;
            $worst = $this->itemsBySignature[$worstSignature] ?? null;
            if (!is_array($current) || !is_array($worst)) {
                continue;
            }

            // If the "worst" is actually better than current, current becomes the new worst.
            if (($this->isBetter)($worst, $current)) {
                $worstSignature = $signature;
            }
        }

        return $worstSignature;
    }
}
