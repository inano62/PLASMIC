<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;                     // ← これ
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTestMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $to;
    public string $subject;
    public string $body;

    public function __construct(string $to, string $subject, string $body = 'Hello from queued job')
    {
        $this->to      = $to;
        $this->subject = $subject;
        $this->body    = $body;
    }

    public function handle(): void
    {
        Mail::raw($this->body, function ($m) {
            $m->to($this->to)->subject($this->subject);
        });
    }
}
