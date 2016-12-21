<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueueLogger implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $name;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            $log  = "Time: ".date("F j, Y, g:i a").PHP_EOL.
                "JOB: ".$this->name.PHP_EOL.
                "-------------------------".PHP_EOL;
            file_put_contents(storage_path('/app/queuelogs/').date("j.n.Y").'.txt', $log, FILE_APPEND);
        }
        catch (\Exception $e){
            dd($e->getMessage());
        }
    }
}
