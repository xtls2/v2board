<?php

namespace App\Jobs;

use App\Models\ServerLog;
use App\Services\ServerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ServerLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $u;
    protected $d;
    protected $userId;
    protected $serverId;
    protected $rate;
    protected $protocol;

    public $tries = 3;
    public $timeout = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($u, $d, $userId, $serverId,$rate, $protocol)
    {
        $this->onQueue('server_log');
        $this->u = $u;
        $this->d = $d;
        $this->userId = $userId;
        $this->serverId = $serverId;
        $this->rate = $rate;
        $this->protocol = $protocol;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $timestamp = strtotime(date('Y-m-d H:0'));

        /**
         * @var ServerLog $serverLog
         */
        $serverLog = ServerLog::where(ServerLog::FIELD_LOG_AT, '>=', $timestamp)
            ->where(ServerLog::FIELD_LOG_AT, '<', $timestamp + 3600)
            ->where(ServerLog::FIELD_SERVER_ID,  $this->serverId)
            ->where(ServerLog::FIELD_USER_ID, $this->userId)
            ->where(ServerLog::FIELD_RATE, $this->rate)
            ->where(ServerLog::FIELD_METHOD, $this->protocol)
            ->lockForUpdate()
            ->first();

        if ($serverLog !== null) {
            $serverLog->addTraffic($this->u, $this->d);
        } else {
            $serverLog = new ServerLog();
            $serverLog->setAttribute(ServerLog::FIELD_USER_ID, $this->userId);
            $serverLog->setAttribute(ServerLog::FIELD_SERVER_ID, $this->serverId);
            $serverLog->setAttribute(ServerLog::FIELD_U, $this->u);
            $serverLog->setAttribute(ServerLog::FIELD_D, $this->d);
            $serverLog->setAttribute(ServerLog::FIELD_RATE, $this->rate);
            $serverLog->setAttribute(ServerLog::FIELD_LOG_AT, $timestamp);
            $serverLog->setAttribute(ServerLog::FIELD_METHOD, $this->protocol);
        }

        if (!$serverLog->save()) {
            throw new Exception("server save failed");
        }
    }

}
