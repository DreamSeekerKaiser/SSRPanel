<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\SsNode;
use App\Http\Models\SsNodeTrafficHourly;
use App\Http\Models\UserTrafficLog;
use Log;

class AutoStatisticsNodeHourlyTraffic extends Command
{
    protected $signature = 'autoStatisticsNodeHourlyTraffic';
    protected $description = '自动统计节点每小时流量';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $jobStartTime = microtime(true);

        $nodeList = SsNode::query()->where('status', 1)->orderBy('id', 'asc')->get();
        foreach ($nodeList as $node) {
            $this->statisticsByNode($node->id);
        }

        $jobEndTime  = microtime(true);
        $jobUsedTime = round(($jobEndTime - $jobStartTime), 4);

        Log::info('执行定时任务【' . $this->description . '】，耗时' . $jobUsedTime . '秒');
    }

    private function statisticsByNode($node_id)
    {
        $start_time = strtotime(date('Y-m-d H:i:s', strtotime("-1 hour")));
        $end_time   = time();

        $query = UserTrafficLog::query()->where('node_id', $node_id)->whereBetween('log_time', [$start_time, $end_time]);

        $u       = $query->sum('u');
        $d       = $query->sum('d');
        $total   = $u + $d;
        $traffic = flowAutoShow($total);

        $obj          = new SsNodeTrafficHourly();
        $obj->node_id = $node_id;
        $obj->u       = $u;
        $obj->d       = $d;
        $obj->total   = $total;
        $obj->traffic = $traffic;
        $obj->save();
    }
}
