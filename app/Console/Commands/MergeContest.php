<?php

namespace App\Console\Commands;

use App\Contest;
use App\ContestEntrant;
use Illuminate\Console\Command;

class MergeContest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merge:contest {contest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merges similar contact which are yet to start';

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
        $contest_id = $this->argument('contest');
        $contest = Contest::find($contest_id);

//        Fetch almost similar contest with matches identical to given contest

        $match_ids = $contest->matches()->pluck('match_id')->toArray();

        $contests = Contest::where('id','!=',$contest->id)
            ->where('is_public','=',1)
            ->where('competition_id','=',$contest->competition_id)
            ->where('type','=',$contest->type)->where('entry_fee','=',$contest->entry_fee)
            ->where('entrants','=',$contest->entrants)->where('award_id','=',$contest->award_id)
            ->where('status','=','Created')->where('start_at','=',$contest->start_at)
            ->where('end_at','=',$contest->end_at)
            ->with('matches',function($q) use($match_ids){
                $q->whereIn('match_id',$match_ids);
            })
            ->get();

//        Filter exact similar contests
        $similar_contests = null;
        foreach ($contests as $c){
            if(count($contest->matches) == count($c->matches)){
                if($similar_contests == null){
                    $similar_contests =$c;
                }else{
                    $similar_contests->merge($c);
                }
            }
        }

//        Required entrants
        $req_count = $contest->entrants-$contest->users()->count();

//        Already entrants ids
        $contest_entrants_ids = ContestEntrant::where('contest_id','=',$contest->id)->pluck('user_id')->toArray();

        foreach($similar_contests as $similar_contest){
//            Fetch entrants other than already from similar contest
            $similar_contest_entrants = ContestEntrant::where('contest_id','=',$similar_contest->id)
                ->whereNotIn('user_id',$contest_entrants_ids)->get();

            if(count($similar_contest_entrants) >= $req_count){
                $loop =0;
                foreach ($similar_contest_entrants as $contest_entrant) {
                    $contest_entrant->contest_id = $contest->id;
                    $contest_entrant->save();
                    $loop++;
                    if($loop ==$req_count){
                        break;
                    }
                }
                break;
            }
        }
    }
}
