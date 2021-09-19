<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Symfony\Component\Console\Output\ConsoleOutput;

class ResetTraffic extends Command
{
    protected $builder;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:traffic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '流量清空';
    /**
     * @var ConsoleOutput
     */
    private $_out;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->_out = new ConsoleOutput();
        $this->builder = User::where(User::FIELD_EXPIRED_AT, '>', time());
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', -1);
        $resetTrafficMethod = config('v2board.reset_traffic_method', 0);
        $this->_out->writeln("reset traffic method: " . $resetTrafficMethod);

        switch ((int)$resetTrafficMethod) {
            // 1 a month
            case 0:
                $this->_resetByMonthFirstDay();
                break;
            // expire day
            case 1:
                $this->_resetByExpireDay();
                break;
            default:
                break;
        }
    }

    /**
     * reset by month first day
     */
    private function _resetByMonthFirstDay(): void
    {
        $builder = $this->builder;
        if ((string)date('d') === '01') {
            $result = $builder->update([
                'u' => 0,
                'd' => 0
            ]);
            $this->_out->writeln("updated count: " . $result);
        }
    }

    /**
     * reset by expire day
     */
    private function _resetByExpireDay(): void
    {
        $builder = $this->builder;
        $lastDay = date('d', strtotime('last day of +0 months'));
        $users = [];
        foreach ($builder->get() as $item) {
            $expireDay = date('d', $item->expired_at);
            $today = date('d');
            if ($expireDay === $today) {
                array_push($users, $item->id);
            }

            if (($today === $lastDay) && $expireDay >= $lastDay) {
                array_push($users, $item->id);
            }
        }
        $result = User::whereIn('id', $users)->update([
            'u' => 0,
            'd' => 0
        ]);
        $this->_out->writeln("updated count: " . $result);

    }
}
