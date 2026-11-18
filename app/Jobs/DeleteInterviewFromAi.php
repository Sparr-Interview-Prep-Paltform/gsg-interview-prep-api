<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteInterviewFromAi implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public int $timeout = 15;

    public int $uniqueFor = 300;

    public function __construct(public int $interviewId) {}

    public function uniqueId(): string
    {
        return (string) $this->interviewId;
    }

    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(): void
    {
        $url = rtrim(
            config('services.python_ai.delete_interview_url'),
            '/'
        );

        $response = Http::timeout(10)
            ->delete($url . '/' . $this->interviewId);

        // A missing interview means there is nothing left to delete.
        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to delete interview from Python AI service.', [
            'interview_id' => $this->interviewId,
            'exception_type' => $exception ? $exception::class : null,
            'message' => $exception?->getMessage(),
        ]);
    }
}
