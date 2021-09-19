<?php

namespace App\Console\Commands;

use App\Jobs\ServerStatJob;
use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderStat;
use App\Models\ServerLog;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

class V2boardStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2board:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计任务';
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
        $this->_statOrder();
        $this->_statServer();
    }

    /**
     * stat order
     */
    private function _statOrder()
    {
        $endAt = strtotime(date('Y-m-d'));
        $startAt = strtotime('-1 day', $endAt);
        $builder = Order::where(Order::FIELD_PAID_AT, '>=', $startAt)
            ->where(Order::FIELD_PAID_AT, '<', $endAt)
            ->whereNotIn(Order::FIELD_STATUS, [Order::STATUS_UNPAID, Order::STATUS_CANCELLED]);
        $orderCount = $builder->count();
        $orderAmount = $builder->sum('total_amount');
        $this->_out->writeln("order count: " . $orderCount);
        $this->_out->writeln("order amount: " . $orderAmount);

        $builder = $builder->where(Order::FIELD_COMMISSION_BALANCE, '!=', 0);
        $commissionCount = $builder->count();
        $commissionAmount = $builder->sum(Order::FIELD_COMMISSION_BALANCE);

        $this->_out->writeln("order commission count: " . $commissionCount);
        $this->_out->writeln("order commission amount: " . $commissionAmount);

        /**
         * @var OrderStat $stat
         */
        $orderStat = OrderStat::where(OrderStat::FIELD_RECORD_AT, $startAt)
            ->where(OrderStat::FIELD_RECORD_TYPE, OrderStat::RECORD_TYPE_D)
            ->first();

        if ($orderStat === null) {
            $this->_out->writeln("order stat record not found");
            $orderStat = new OrderStat();
        }

        $orderStat->setAttribute(OrderStat::FIELD_ORDER_COUNT, $orderCount);
        $orderStat->setAttribute(OrderStat::FIELD_ORDER_AMOUNT, $orderAmount);
        $orderStat->setAttribute(OrderStat::FIELD_COMMISSION_COUNT, $commissionCount);
        $orderStat->setAttribute(OrderStat::FIELD_COMMISSION_AMOUNT, $commissionAmount);
        $orderStat->setAttribute(OrderStat::FIELD_RECORD_TYPE, OrderStat::RECORD_TYPE_D);
        $orderStat->setAttribute(OrderStat::FIELD_RECORD_AT, $startAt);
        if (!$orderStat->save()) {
            $this->_out->writeln("order stats save failed");
        } else {
            $this->_out->writeln("order status save success");
        }
    }

    /**
     * stat server
     */
    private function _statServer()
    {
        $endAt = strtotime(date('Y-m-d'));
        $startAt = strtotime('-1 day', $endAt);
        $statistics = ServerLog::select([
            'server_id',
            'method as server_type',
            DB::raw("sum(u) as u"),
            DB::raw("sum(d) as d"),
        ])
            ->where('log_at', '>=', $startAt)
            ->where('log_at', '<', $endAt)
            ->groupBy('server_id', 'method')
            ->get()
            ->toArray();

        foreach ($statistics as $statistic) {
            $statistic['record_type'] = 'd';
            $statistic['record_at'] = $startAt;
            ServerStatJob::dispatch($statistic);
        }

        $this->_out->writeln("server stats job count: " . count($statistics));

        $this->_out->writeln("server stats job is scheduled");

    }
}
