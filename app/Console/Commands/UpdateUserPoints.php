<?php

namespace App\Console\Commands;

use App\AwardItem;
use App\Contest;
use App\ContestEntrant;
use App\ContestMatch;
use App\PlayerLineup;
use App\UserPick;
use Illuminate\Console\Command;

class UpdateUserPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:user-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate user points and rank';

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
        foreach (Contest::where('status','=','Ongoing')->get() as $contest){
            $match_ids = ContestMatch::where('contest_id','=',$contest->id)->pluck('match_id')->toArray();
            foreach (ContestEntrant::where('contest_id','=',$contest->id)->get() as $contest_entrant){
                $contest_entrant->points = 0;
                foreach (UserPick::where('contest_id','=',$contest->id)->where('user_id','=',$contest_entrant->user_id)->get() as $pick){
                    $player_lineup = PlayerLineup::where('player_id',$pick->player_id)->whereIn('match_id',$match_ids)->first();
                    if($player_lineup != null){
                        $contest_entrant->points = $contest_entrant->points + $player_lineup->points;
                        $contest_entrant->save();
                    }
                }
            }
            $paid_contestants = ContestEntrant::where('is_active','=',1)->where('contest_id','=',$contest->id)->orderBy('points')->get();
            foreach ($paid_contestants as $key =>$contest_entrant){
                $contest_entrant->rank = $key+1;
                $contest_entrant->save();
                
                $award = AwardItem::where('award_id','=',$contest->award_id)->where('rank','=',(integer)$contest_entrant->rank)->first();
                if($award !=null){
                    if($award->type == 'reward'){
                        $contest_entrant->award = $award->reward;
                    }else{
                        $contest_entrant->award = ($contest->entry_fee * $contest->entrants*0.9) * $award->share;
                    }
                    $contest_entrant->save();
                }
            }
        }

    }
}
