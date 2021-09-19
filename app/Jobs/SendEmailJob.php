<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Models\MailLog;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->delay(now()->addSecond(2));
        $this->onQueue('send_email');
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (config('v2board.email_host')) {
            Config::set('mail.host', config('v2board.email_host', env('mail.host')));
            Config::set('mail.port', config('v2board.email_port', env('mail.port')));
            Config::set('mail.encryption', config('v2board.email_encryption', env('mail.encryption')));
            Config::set('mail.username', config('v2board.email_username', env('mail.username')));
            Config::set('mail.password', config('v2board.email_password', env('mail.password')));
            Config::set('mail.from.address', config('v2board.email_from_address', env('mail.from.address')));
            Config::set('mail.from.name', strstr(config('v2board.email_from_address', env('mail.from.address')),
                '@', true));
        }

        $params = $this->params;
        $email = $params['email'];
        $subject = $params['subject'];
        $templateName = 'mail.' . config('v2board.email_template', 'default') . '.' . $params['template_name'];
        try {
            Mail::send(
                $templateName,
                $params['template_value'],
                function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                }
            );
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $mailLog = new MailLog();
        $mailLog->setAttribute(MailLog::FIELD_EMAIL, $email);
        $mailLog->setAttribute(MailLog::FIELD_SUBJECT, $subject);
        $mailLog->setAttribute(MailLog::FIELD_TEMPLATE_NAME, $templateName);
        $mailLog->setAttribute(MailLog::FIELD_ERROR, $error ?? NULL);
        $mailLog->save();
    }
}
