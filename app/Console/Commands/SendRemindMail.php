<?php

namespace App\Console\Commands;

use App\Services\MailService;
use Illuminate\Console\Command;
use App\Models\User;
use Symfony\Component\Console\Output\ConsoleOutput;

class SendRemindMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:remindMail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送提醒邮件';
    /**
     * @var ConsoleOutput
     */
    private $_out;
    /**
     * @var int
     */
    private $_sendCount;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_out = new ConsoleOutput();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $mailService = new MailService();
        $users = User::all();
        $this->_out->writeln("users count: " . count($users));
        $this->_sendCount = 0;
        foreach ($users as $user) {
            /**
             * @var User $user
             */
            if ($user->getAttribute(User::FIELD_REMIND_EXPIRE)) {
                $mailService->remindExpire($user);
                $this->_sendCount++;
            }
        }

        $this->_out->writeln("send count: " . $this->_sendCount);
    }


}
