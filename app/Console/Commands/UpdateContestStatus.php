<?php

namespace App\Console\Commands;

use App\Contest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateContestStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:contest-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Contest Status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $contests = Contest::where('status','=','Created')->get();
        foreach ($contests as $contest){
            $startTime = Carbon::parse($contest->start_at);
            $currentTime = Carbon::now();
            $time_left = $currentTime->diffInMinutes($startTime,false);
            if($time_left <= CNF_LOCKTIME){
                $this->call('merge:contest', [
                    'contest' => $contest->id
                ]);
                if($contest->entrants > $contest->users->count()){
                    $contest->status = 'Cancelled';
                }else{
                    $contest->status = 'Locked';
                }
            }
            $contest->save();
        }
    }
}
