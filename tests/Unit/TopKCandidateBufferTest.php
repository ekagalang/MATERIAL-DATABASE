<?php

use App\Services\Calculation\Support\TopKCandidateBuffer;

function makeTopKBuffer(int $capacity = 3): TopKCandidateBuffer
{
    return new TopKCandidateBuffer(
        $capacity,
        static fn(array $new, array $old): bool => ((float) ($new['total_cost'] ?? 0)) < ((float) ($old['total_cost'] ?? 0)),
        static fn(array $a, array $b): int => ((float) ($a['total_cost'] ?? 0)) <=> ((float) ($b['total_cost'] ?? 0)),
    );
}

test('topk buffer keeps only best cheapest candidates', function () {
    $buffer = makeTopKBuffer(3);

    $buffer->push(['signature' => 'a', 'total_cost' => 500]);
    $buffer->push(['signature' => 'b', 'total_cost' => 200]);
    $buffer->push(['signature' => 'c', 'total_cost' => 300]);
    $buffer->push(['signature' => 'd', 'total_cost' => 100]);
    $buffer->push(['signature' => 'e', 'total_cost' => 400]);

    $all = $buffer->all();

    expect(array_column($all, 'signature'))->toBe(['d', 'b', 'c']);
    expect($buffer->count())->toBe(3);
});

test('topk buffer replaces same signature when new candidate is better', function () {
    $buffer = makeTopKBuffer(2);

    $buffer->push(['signature' => 'same', 'total_cost' => 250]);
    $buffer->push(['signature' => 'same', 'total_cost' => 200]);

    $all = $buffer->all();

    expect($all)->toHaveCount(1);
    expect((float) $all[0]['total_cost'])->toBe(200.0);

    $stats = $buffer->stats();
    expect($stats['inserted'])->toBe(1);
    expect($stats['replaced'])->toBe(1);
});

test('topk buffer dedupes same signature when new candidate is worse', function () {
    $buffer = makeTopKBuffer(2);

    $buffer->push(['signature' => 'same', 'total_cost' => 200]);
    $buffer->push(['signature' => 'same', 'total_cost' => 260]);

    $all = $buffer->all();

    expect($all)->toHaveCount(1);
    expect((float) $all[0]['total_cost'])->toBe(200.0);

    $stats = $buffer->stats();
    expect($stats['deduped'])->toBe(1);
});

test('topk buffer discards worse candidate when full', function () {
    $buffer = makeTopKBuffer(2);

    $buffer->push(['signature' => 'a', 'total_cost' => 100]);
    $buffer->push(['signature' => 'b', 'total_cost' => 200]);
    $buffer->push(['signature' => 'c', 'total_cost' => 300]);

    $all = $buffer->all();
    $stats = $buffer->stats();

    expect(array_column($all, 'signature'))->toBe(['a', 'b']);
    expect($stats['discarded'])->toBe(1);
    expect($stats['size'])->toBe(2);
    expect($stats['capacity'])->toBe(2);
});

test('topk buffer exposes current worst candidate', function () {
    $buffer = makeTopKBuffer(3);

    $buffer->push(['signature' => 'a', 'total_cost' => 100]);
    $buffer->push(['signature' => 'b', 'total_cost' => 200]);
    $buffer->push(['signature' => 'c', 'total_cost' => 150]);

    $worst = $buffer->worst();

    expect($worst)->not->toBeNull();
    expect($worst['signature'])->toBe('b');
    expect((float) $worst['total_cost'])->toBe(200.0);
});
