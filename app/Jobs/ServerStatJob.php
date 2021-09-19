<?php

namespace App\Jobs;

use App\Models\ServerStat;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ServerStatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $statistic;

    public $tries = 3;
    public $timeout = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $statistic)
    {
        $this->onQueue('stat_server');
        $this->statistic = $statistic;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $statistic = $this->statistic;

        /**
         * @var ServerStat $stats
         */
        $stats = ServerStat::where(ServerStat::FIELD_RECORD_AT, $statistic[ServerStat::FIELD_RECORD_AT])
            ->where(ServerStat::FIELD_SERVER_ID, $statistic[ServerStat::FIELD_SERVER_ID])
            ->first();

        if ($stats === null) {
            $stats = new ServerStat();
            $stats->setAttribute(ServerStat::FIELD_SERVER_ID, $statistic[ServerStat::FIELD_SERVER_ID]);
            $stats->setAttribute(ServerStat::FIELD_SERVER_TYPE, $statistic[ServerStat::FIELD_SERVER_TYPE]);
        }

        $stats->setAttribute(ServerStat::FIELD_U, $statistic[ServerStat::FIELD_U]);
        $stats->setAttribute(ServerStat::FIELD_D, $statistic[ServerStat::FIELD_D]);
        $stats->setAttribute(ServerStat::FIELD_RECORD_TYPE, $statistic[ServerStat::FIELD_RECORD_TYPE]);
        $stats->setAttribute(ServerStat::FIELD_RECORD_AT, $statistic[ServerStat::FIELD_RECORD_AT]);

        if (!$stats->save()) {
            throw new Exception(500, '节点统计数据更新失败');
        }

    }
}
