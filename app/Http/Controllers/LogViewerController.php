<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class LogViewerController extends Controller
{
    public function index(Request $request)
    {
        $displayTimezone = (string) config('app.timezone', 'UTC');
        $sourceTimezone = (string) config('logging.source_timezone', $displayTimezone);
        $statusFilter = strtolower((string) $request->input('status', 'all'));
        $levelFilter = strtolower((string) $request->input('level', 'all'));
        $fileFilter = (string) $request->input('file', 'all');
        $searchFilter = trim((string) $request->input('search', ''));
        $dateFilter = trim((string) $request->input('date', ''));

        $logDirectory = storage_path('logs');
        $logFiles = File::isDirectory($logDirectory)
            ? collect(File::files($logDirectory))
                ->filter(static fn($file) => strtolower($file->getExtension()) === 'log')
                ->sortByDesc(static fn($file) => $file->getMTime())
                ->values()
            : collect();

        $fileOptions = $logFiles->map(static fn($file) => $file->getFilename())->values();
        $selectedFiles =
            $fileFilter === 'all'
                ? $fileOptions
                : $fileOptions->filter(static fn($filename) => $filename === $fileFilter)->values();

        $entries = collect();
        foreach ($selectedFiles as $filename) {
            $entries = $entries->merge(
                $this->parseLogFile(storage_path('logs/' . $filename), $filename, $sourceTimezone),
            );
        }

        $entries = $entries
            ->sortByDesc('sortable_timestamp')
            ->values()
            ->map(static function (array $entry) {
                unset($entry['sortable_timestamp']);

                return $entry;
            });

        $filteredEntries = $entries
            ->filter(function (array $entry) use ($statusFilter, $levelFilter, $searchFilter, $dateFilter) {
                if ($statusFilter !== 'all' && $entry['status'] !== $statusFilter) {
                    return false;
                }

                if ($levelFilter !== 'all' && $entry['level'] !== $levelFilter) {
                    return false;
                }

                if ($dateFilter !== '' && $entry['date'] !== $dateFilter) {
                    return false;
                }

                if ($searchFilter !== '') {
                    $haystack = strtolower(
                        implode(' ', [
                            $entry['message'],
                            $entry['details'],
                            $entry['channel'],
                            $entry['file'],
                            $entry['level'],
                            $entry['status'],
                        ]),
                    );

                    if (!str_contains($haystack, strtolower($searchFilter))) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        $perPageOptions = [25, 50, 100, 200];
        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 50;
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $filteredEntries->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $logs = new LengthAwarePaginator($currentItems, $filteredEntries->count(), $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $statusOptions = [
            'all' => 'Semua Status',
            'success' => 'Berhasil',
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
            'debug' => 'Debug',
            'other' => 'Lainnya',
        ];

        $levelOptions = [
            'all' => 'Semua Level',
            'emergency' => 'Emergency',
            'alert' => 'Alert',
            'critical' => 'Critical',
            'error' => 'Error',
            'warning' => 'Warning',
            'notice' => 'Notice',
            'info' => 'Info',
            'debug' => 'Debug',
        ];

        return view('log.index', [
            'logs' => $logs,
            'totalEntries' => $entries->count(),
            'filteredEntriesCount' => $filteredEntries->count(),
            'statusOptions' => $statusOptions,
            'levelOptions' => $levelOptions,
            'fileOptions' => $fileOptions,
            'perPageOptions' => $perPageOptions,
            'displayTimezone' => $displayTimezone,
            'displayTimezoneOffset' => Carbon::now($displayTimezone)->format('P'),
            'sourceTimezone' => $sourceTimezone,
            'filters' => [
                'status' => $statusFilter,
                'level' => $levelFilter,
                'file' => $fileFilter,
                'search' => $searchFilter,
                'date' => $dateFilter,
                'per_page' => $perPage,
            ],
        ]);
    }

    protected function parseLogFile(string $path, string $filename, string $sourceTimezone): Collection
    {
        if (!File::exists($path)) {
            return collect();
        }

        $entries = [];
        $currentEntry = null;

        $logFile = new \SplFileObject($path, 'r');
        while (!$logFile->eof()) {
            $line = rtrim((string) $logFile->fgets(), "\r\n");

            if ($this->isLogHeader($line, $parsedHeader)) {
                if ($currentEntry !== null) {
                    $entries[] = $this->normalizeEntry($currentEntry, $filename, $sourceTimezone);
                }

                $currentEntry = [
                    'timestamp' => $parsedHeader['timestamp'],
                    'channel' => strtolower($parsedHeader['channel']),
                    'level' => strtolower($parsedHeader['level']),
                    'message' => $parsedHeader['message'],
                    'details' => [],
                ];

                continue;
            }

            if ($currentEntry !== null) {
                $currentEntry['details'][] = $line;
            }
        }

        if ($currentEntry !== null) {
            $entries[] = $this->normalizeEntry($currentEntry, $filename, $sourceTimezone);
        }

        return collect($entries);
    }

    protected function isLogHeader(string $line, ?array &$parsedHeader = null): bool
    {
        $pattern = '/^\[(?<timestamp>[^\]]+)\]\s+(?<channel>[A-Za-z0-9_.-]+)\.(?<level>[A-Z]+):\s*(?<message>.*)$/';

        if (!preg_match($pattern, $line, $matches)) {
            $parsedHeader = null;

            return false;
        }

        $parsedHeader = [
            'timestamp' => trim($matches['timestamp']),
            'channel' => trim($matches['channel']),
            'level' => trim($matches['level']),
            'message' => (string) ($matches['message'] ?? ''),
        ];

        return true;
    }

    protected function normalizeEntry(array $entry, string $filename, string $sourceTimezone): array
    {
        $message = trim((string) ($entry['message'] ?? ''));
        $details = trim(implode(PHP_EOL, $entry['details'] ?? []));
        $level = strtolower((string) ($entry['level'] ?? 'info'));
        $displayTimezone = (string) config('app.timezone', 'UTC');

        $parsedTimestamp = null;
        try {
            if ($sourceTimezone === $displayTimezone) {
                $parsedTimestamp = Carbon::parse((string) ($entry['timestamp'] ?? ''), $displayTimezone);
            } else {
                $parsedTimestamp = Carbon::parse((string) ($entry['timestamp'] ?? ''), $sourceTimezone)->setTimezone(
                    $displayTimezone,
                );
            }
        } catch (\Throwable $e) {
            $parsedTimestamp = null;
        }

        $status = $this->resolveStatus($level, $message, $details);

        return [
            'timestamp' => $parsedTimestamp
                ? $parsedTimestamp->format('Y-m-d H:i:s')
                : (string) ($entry['timestamp'] ?? '-'),
            'date' => $parsedTimestamp ? $parsedTimestamp->format('Y-m-d') : '',
            'sortable_timestamp' => $parsedTimestamp ? $parsedTimestamp->getTimestamp() : 0,
            'channel' => (string) ($entry['channel'] ?? '-'),
            'level' => $level,
            'status' => $status,
            'message' => $message !== '' ? $message : '-',
            'details' => $details,
            'file' => $filename,
        ];
    }

    protected function resolveStatus(string $level, string $message, string $details): string
    {
        if (in_array($level, ['emergency', 'alert', 'critical', 'error'], true)) {
            return 'error';
        }

        if ($level === 'warning') {
            return 'warning';
        }

        if ($level === 'debug') {
            return 'debug';
        }

        $text = strtolower($message . ' ' . $details);
        if (str_contains($text, 'success') || str_contains($text, 'sukses') || str_contains($text, 'berhasil')) {
            return 'success';
        }

        if (in_array($level, ['info', 'notice'], true)) {
            return 'info';
        }

        return 'other';
    }
}
