<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Jobs\OrderHandleJob;
use Symfony\Component\Console\Output\ConsoleOutput;


class CheckOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '订单检查任务';

    /**
     * @var ConsoleOutput $_out
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
        $orders = Order::whereIn(Order::FIELD_STATUS, [Order::STATUS_UNPAID, Order::STATUS_PENDING])->get();
        foreach ($orders as $order) {
            /**
             * @var Order $order
             */
            OrderHandleJob::dispatch($order->getAttribute(Order::FIELD_TRADE_NO));
        }
        $this->_out->writeln("orders count: " .  count($orders));
    }
}

