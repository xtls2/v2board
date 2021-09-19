<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderHandleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Order $order
     */
    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tradeNo)
    {
        $this->onQueue('order_handle');
        $this->order = Order::findByTradeNo($tradeNo);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->order) {
            return;
        }
        $order = $this->order;
        $orderStatus = $order->getAttribute(Order::FIELD_STATUS);

        switch ($orderStatus) {
            case Order::STATUS_UNPAID:
                if ($order->getAttribute(Order::FIELD_CREATED_AT) <= (time()- 1800)) {
                    $order->cancel();
                }
                break;
            case Order::STATUS_PENDING:
                $order->open();
                break;
        }
    }
}
