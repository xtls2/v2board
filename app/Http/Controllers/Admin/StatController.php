<?php

namespace App\Http\Controllers\Admin;

use App\Models\ServerShadowsocks;
use App\Models\ServerTrojan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Order;
use App\Models\OrderStat;
use App\Models\ServerStat;
use Illuminate\Support\Facades\DB;

class StatController extends Controller
{

    /**
     * override
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function override(Request $request)
    {
        return response([
            'data' => [
                'month_income' => Order::sumMonthIncome(),
                'month_register_total' => User::countMonthRegister(),
                'ticket_pendding_total' => Ticket::countTicketPending(),
                'commission_pendding_total' => Order::countCommissionPending(),
                'day_income' => Order::sumDayIncome(),
                'last_month_income' => Order::sumLastMonthIncome()
            ]
        ]);
    }

    /**
     * order
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function order(Request $request)
    {
        $orderStats = OrderStat::where(OrderStat::FIELD_RECORD_TYPE, OrderStat::RECORD_TYPE_D)
            ->limit(31)
            ->orderBy(OrderStat::FIELD_RECORD_AT, "DESC")
            ->get();
        $result = [];

        /**
         * @var OrderStat $stat
         */
        foreach ($orderStats as $stat) {
            $date = date('m-d', $stat->getAttribute(OrderStat::FIELD_RECORD_AT));
            array_push($result, [
                'type' => '收款金额',
                'date' => $date,
                'value' => $stat->getAttribute(OrderStat::FIELD_ORDER_AMOUNT) / 100
            ]);

            array_push($result, [
                'type' => '收款笔数',
                'date' => $date,
                'value' => $stat->getAttribute(OrderStat::FIELD_ORDER_COUNT)
            ]);

            array_push($result, [
                'type' => '佣金金额',
                'date' => $date,
                'value' => $stat->getAttribute(OrderStat::FIELD_COMMISSION_AMOUNT) / 100
            ]);

            array_push($result, [
                'type' => '佣金笔数',
                'date' => $date,
                'value' => $stat->getAttribute(OrderStat::FIELD_COMMISSION_COUNT)
            ]);
        }
        return response([
            'data' => array_reverse($result)
        ]);
    }

    public function serverLastRank()
    {
        $servers = [
            'shadowsocks' => ServerShadowsocks::where(ServerShadowsocks::FIELD_PARENT_ID, 0)->get(),
            'vmess' => Server::where(Server::FIELD_PARENT_ID, 0)->get(),
            'trojan' => ServerTrojan::where(Server::FIELD_PARENT_ID, 0)->get()
        ];

        $timestamp = strtotime('-1 day', strtotime(date('Y-m-d')));
        $statistics = ServerStat::select([
            ServerStat::FIELD_SERVER_ID,
            ServerStat::FIELD_SERVER_TYPE,
            ServerStat::FIELD_U,
            ServerStat::FIELD_D,
            DB::raw('(u+d) as total')
        ])
            ->where(ServerStat::FIELD_RECORD_AT, '>=', $timestamp)
            ->where(ServerStat::FIELD_RECORD_TYPE, ServerStat::RECORD_TYPE_DAY)
            ->limit(10)
            ->orderBy('total', "DESC")
            ->get();

        //var_dump($statistics);
        foreach ($statistics as $stats) {
            /**
             * @var ServerStat $stats
             */
            foreach ($servers[$stats->getAttribute(ServerStat::FIELD_SERVER_TYPE)] as $server) {
                /**
                 * @var Server $server
                 */
                if ($server->getKey() === $stats->getAttribute(ServerStat::FIELD_SERVER_ID)) {
                    $stats['server_name'] = $server->getAttribute(Server::FIELD_NAME);
                }
            }
            $stats['total'] = $stats['total'] / 1073741824;
        }
        $statsData = $statistics->toArray();
        array_multisort(array_column($statsData, 'total'), SORT_DESC, $statsData);
        return response([
            'data' => $statsData
        ]);
    }
}

