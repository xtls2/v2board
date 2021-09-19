<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

class CheckCommission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:commission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '返佣服务';
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
        $this->_autoCheck();
        $this->_autoPayCommission();
    }

    /**
     * auto check
     */
    private function _autoCheck()
    {
        if ((int)config('v2board.commission_auto_check_enable', 1)) {
            $this->_out->writeln("commission auto check enable");
            $result = Order::where(Order::FIELD_COMMISSION_STATUS, Order::COMMISSION_STATUS_NEW)
                ->where(Order::FIELD_INVITE_USER_ID, '>', 0)
                ->where(Order::FIELD_STATUS, Order::STATUS_COMPLETED)
                ->where(Order::FIELD_UPDATED_AT, '<=', strtotime('-3 day', time()))
                ->update([
                    Order::FIELD_COMMISSION_STATUS => Order::COMMISSION_STATUS_PENDING
                ]);
            $this->_out->writeln("update commission status. result :" . $result);
        } else {
            $this->_out->writeln("commission auto check disable");
        }
    }

    /**
     * auto pay commission
     */
    private function _autoPayCommission()
    {
        $orders = Order::where(Order::FIELD_COMMISSION_STATUS, Order::COMMISSION_STATUS_PENDING)
            ->where(Order::FIELD_INVITE_USER_ID, '>', 0)
            ->get();
        DB::beginTransaction();

        $configWithdrawCloseEnable = (bool)config('v2board.withdraw_close_enable', 0);
        if ($configWithdrawCloseEnable) {
            $this->_out->writeln("withdraw close enable");
        } else {
            $this->_out->writeln("withdraw close disable");
        }

        $this->_out->writeln("find pay commission orders count :" . count($orders));
        /**
         * @var Order $order
         */
        foreach ($orders as $order) {
            /**
             * @var User $inviter
             */
            $inviter = User::find($order->getAttribute(User::FIELD_INVITE_USER_ID));
            if (!$inviter === null) {
                $this->_out->writeln("inviter not found. user_id: " . $order->getAttribute(User::FIELD_INVITE_USER_ID));
                continue;
            }


            if ($configWithdrawCloseEnable) {
                $inviter->setAttribute(User::FIELD_BALANCE, $inviter->getAttribute(User::FIELD_BALANCE) + $order->getAttribute(Order::FIELD_COMMISSION_BALANCE));
            } else {
                $inviter->setAttribute(User::FIELD_COMMISSION_BALANCE, $inviter->getAttribute(User::FIELD_COMMISSION_BALANCE) +
                    $order->getAttribute(Order::FIELD_COMMISSION_BALANCE));
            }


            if (!$inviter->save()) {
                $this->_out->writeln("inviter save failed. user_id : " . $inviter->getKey());
                DB::rollBack();
                break;
            }

            $order->setAttribute(Order::FIELD_COMMISSION_STATUS, Order::COMMISSION_STATUS_VALID);
            if (!$order->save()) {
                $this->_out->writeln("order save failed. user_id : " . $order->getKey());
                DB::rollBack();
                break;
            }
        }

        DB::commit();

    }

}
