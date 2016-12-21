<?php

namespace App\Http\Controllers\Api;

use App\Award;
use App\AwardItem;
use App\Contest;
use App\ContestEntrant;
use App\ContestInvite;
use App\Lineup;
use App\LineupPick;
use App\Match;
use App\Player;
use App\UserPick;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ContestController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function postContest(Request $request)
    {
        $contest = new Contest();
        $contest->user_id = $request->user()->id;
        $contest->competition_id = $request->competition_id;
        $contest->type = $request->type;
        $contest->entrants = $request->entrants?:2;
        $contest->entry_fee = $request->entry_fee?:0;
        $contest->award_id = $request->award_id?:1;
        $contest->is_public = $request->is_public?:0;
        $contest->save();
        $next_date = date('Y-m-d', strtotime($request->match_date.' +1 day'));
        $matches = Match::where('competition_id','=',$request->competition_id)
            ->whereDate('scheduled_on', '<', $next_date)->where('scheduled_on', '>', $request->match_date)->get();
        foreach($matches as $match){
            $contest->matches()->attach($match->id);
        }
        $contest->start_at =  $contest->matches()->orderBy('scheduled_on')->first()->scheduled_on;
        $contest->est_end_at =  Carbon::parse($contest->matches()->orderBy('scheduled_on','desc')->first()->scheduled_on)->addMinutes(90);
        $contest->save();
        $data['status'] = 'success';
        $data['message'] = 'Contest created.';
        return $data;
    }
    public function postPicks(Request $request)
    {
        $user_id = $request->user()->id;
        $contest_id = $request->contest_id;
        $player_ids = array();
        if(ContestEntrant::where('contest_id','=',$contest_id)->where('user_id','=',$user_id)->first() == null){
            $contest_entrant = new ContestEntrant();
            $contest_entrant->contest_id = $contest_id;
            $contest_entrant->user_id = $user_id;
            $contest_entrant->is_active = 0;
            $contest_entrant->save();
        }
        if(isset($request->players)){
            foreach( json_decode($request->players) as $key => $player){
                $player_ids[$key] = $player->player->id;
            }
        }else{
            $player_ids = $request->player_id;
        }
        UserPick::where('user_id','=',$user_id)->where('contest_id','=',$contest_id)->delete();
        foreach($player_ids as $player_id){
            if(UserPick::where('contest_id','=',$contest_id)->where('player_id','=',$player_id)->count() > 0){
                $data['status'] = 'error';
                $data['message'] = 'One or more player already taken.';
                return $data;
            }
            $pick = new UserPick();
            $pick->user_id = $user_id;
            $pick->contest_id = $contest_id;
            $pick->competition_id = Contest::find($contest_id)->competition_id;
            $pick->team_id = Player::find($player_id)->team_id;
            $pick->player_id = $player_id;
            $pick->save();
        }
        if(isset($request->team_id) && $request->team_id !=''){
            foreach (Player::where('team_id','=',$request->team_id)->whereIn('position',['Goalkeeper','Defender'])->get() as $player){
                $pick = new UserPick();
                $pick->user_id = $user_id;
                $pick->contest_id = $contest_id;
                $pick->competition_id = Contest::find($contest_id)->competition_id;
                $pick->team_id = $player->team_id;
                $pick->player_id = $player->id;
                $pick->save();
            }
        }
        ContestInvite::where('to_id','=',$user_id)->where('contest_id','=',$contest_id)->delete();
        $data['status'] = 'success';
        $data['message'] = 'Player picks added.';
        return $data;

    }
    public function postLineup(Request $request)
    {
        $user_id = $request->user()->id;
        $player_ids = array();
        if(isset($request->players)){
            foreach( json_decode($request->players) as $key => $player){
                $player_ids[$key] = $player->player->id;
            }
        }else{
            $player_ids = $request->player_id;
        }
        $lineup = new Lineup();
        $lineup->user_id = $user_id;
        $lineup->name = $request->name;
        $lineup->save();
        foreach($player_ids as $player_id){
            $pick = new LineupPick();
            $pick->lineup_id = $lineup->id;
            $pick->player_id = $player_id;
            $pick->save();
        }
        $data['status'] = 'success';
        $data['message'] = 'Lineup saved.';
        return $data;

    }
    public function postJoin(Request $request){
        $user_id = $request->user()->id;
        $contest_id = $request->contest_id;
        if($contest_entrant = ContestEntrant::where('contest_id','=',$contest_id)->where('user_id','=',$user_id)->first() != null){
            $contest_entrant->is_active = 1;
            $contest_entrant->save();
        }
        $data['status'] = 'success';
        $data['message'] = 'User joined contest.';
        return $data;
    }
    public function postLeave(Request $request){
        $user_id = $request->user()->id;
        $contest_id = $request->contest_id;
        ContestEntrant::where('contest_id','=',$contest_id)->where('user_id','=',$user_id)->delete();
        UserPick::where('contest_id','=',$contest_id)->where('user_id','=',$user_id)->delete();
        $data['status'] = 'success';
        $data['message'] = 'User left contest.';
        return $data;
    }
    public function postInvite(Request $request){
        $from = $request->user();
        foreach(json_decode($request->user_id) as $user_id){
            foreach (json_decode($request->contest_id) as $contest_id){
                $to = User::find($user_id);
                $contest = Contest::find($contest_id);
                $contest_invite = new ContestInvite();
                $contest_invite->from_id = $from->id;
                $contest_invite->to_id = $to->id;
                $contest_invite->contest_id = $contest->id;
                $contest_invite->save();
            }
        }
        $data['status'] = 'success';
        $data['message'] = 'Invitation Sent';
        return $data;
    }
    public function postClaim(Request $request){
        $contest_entrant = ContestEntrant::find($request->id);
        $contest_entrant->award_claimed = 1;
        $contest_entrant->save();
        $data['status'] = 'success';
        $data['message'] = 'Claim noted';
        return $data;
    }
}
