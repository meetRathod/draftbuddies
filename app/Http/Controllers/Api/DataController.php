<?php

namespace App\Http\Controllers\Api;

use App\Award;
use App\Competition;
use App\Contest;
use App\ContestEntrant;
use App\File;
use App\Friend;
use App\Http\Controllers\Controller;
use App\Match;
use App\Page;
use App\Player;
use App\PlayerLineup;
use App\PlayerSalary;
use App\PlayerStat;
use App\Team;
use App\User;
use App\UserPick;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function getUploads(Request $request)
    {
        $uploads = File::all();
        if(isset($request->name)){
            $uploads = File::where('name','=',$request->name)->get();
        }
        foreach ($uploads as $upload){
            $data['data'][$upload->name] = $upload->path;
        }
        $data['status']= "Success";
        return $data;
    }
    public function getPages(Request $request)
    {
        $pages = Page::all();
        $name = explode(',',$request->name);
        if(isset($request->name)){
            $pages = Page::whereIn('name',$name)->get();
        }
        foreach ($pages as $page){
            $data['data'][$page->name] = $page->text;
        }
        $data['status']= "Success";
        return $data;
    }
    public function getUsers(Request $request)
    {
        $data['status'] = 'success';
        $data['message'] = '';
        $data['data'] = User::where('id','!=',$request->user()->id)->where(function ($query) use($request) {
            $query->where('name','like','%'.$request->keyword.'%')
                ->orWhere('username','like','%'.$request->keyword.'%');
        })->select('id','name','username')->get();
        foreach ($data['data'] as $key=> $user){
            $requested = $request->user()->myFriends()
                ->where('friend_id','=',$user->id)->count();

            $accepted =  $request->user()->friendsOf()
                ->where('user_id','=',$user->id)->count();

            if($requested > 0 ){
                $data['data'][$key]['is_sent_pending'] = Friend::where('user_id','=',$request->user()->id)
                    ->where('friend_id','=',$user->id)->first()->status ==0 ?true:false;

                $data['data'][$key]['is_friend'] = !$data['data'][$key]['is_sent_pending'];

                $data['data'][$key]['request_id'] = Friend::where('user_id','=',$request->user()->id)
                    ->where('friend_id','=',$user->id)->first()->id;

            } elseif($accepted > 0){

                $data['data'][$key]['is_received_pending'] = Friend::where('user_id','=',$user->id)
                    ->where('friend_id','=',$request->user()->id)->first()->status ==0 ?true:false;

                $data['data'][$key]['request_id'] = Friend::where('user_id','=',$user->id)
                    ->where('friend_id','=',$request->user()->id)->first()->id;

                $data['data'][$key]['is_friend'] = !$data['data'][$key]['is_received_pending'];

            }else{
                $data['data'][$key]['is_friend'] = false;
            }
        }
        return $data;
    }
    public function getCompetitions(Request $request)
    {
        $data['status'] = 'success';
        $data['message'] = '';
        $data['data'] = Competition::api()->get();
        return $data;
    }
    public function getMatches(Request $request)
    {
        $data['status'] = 'success';
        $next_date = date('Y-m-d', strtotime($request->match_date.' +1 day'));
        $data['data'] = Match::where('competition_id','=',$request->competition_id)
            ->whereDate('scheduled_on', '<', $next_date)->where('scheduled_on', '>', $request->match_date)->with(['team1','team2'])->api()->get();
        return $data;
    }
    public function getAwards(Request $request)
    {
        $data['status'] = 'success';
        $data['data'] = Award::api()->get();
        return $data;
    }
    public function getContest(Request $request,$id=null)
    {
        if($id ==null){
            $contests = Contest::where('is_public','=',1)
                ->where('status','=','Locked')->orWhere('status','=','Created');

            $contests = $contests->with('award')->get();

            foreach ($contests as $key => $contest){
                $contests[$key]['entrant_count'] = ContestEntrant::where('contest_id','=',$contest->id)->count();
                if(ContestEntrant::where('contest_id','=',$contest->id)->where('user_id','=',$request->user()->id)->first() == null){
                    $contests[$key]['is_user_entrant'] = false;
                }else{
                    $contests[$key]['is_user_entrant'] = true;
                    $contests[$key]['user_picks'] = UserPick::where('contest_id','=',$contest->id)->where('user_id','=',$request->user()->id)
                        ->pluck('player_id');
                    $teams = UserPick::where('contest_id','=',$contest->id)->where('user_id','=',$request->user()->id)->pluck('team_id')->toArray();
                    $c = array_count_values($teams);
                    $contests[$key]['team_id'] = array_search(max($c), $c);

                }
            }
            $final_data = $contests;
        }else{
            $contest = Contest::where('id','=',$id)->first();
            $final_data['competition'] = Competition::find($contest->competition_id)->name;
            $final_data['competition_code'] = Competition::find($contest->competition_id)->code;
            $final_data['type'] = $contest->type;
            $final_data['entrants'] = $contest->entrants;
            $final_data['entry_fee'] = $contest->entry_fee;
            $final_data['start_at'] = $contest->start_at;
            $final_data['matches'] = $contest->matches()->with(['team1','team2'])->get();
            $final_data['award'] = $contest->award()->with('items')->first();
            $final_data['users'] = $contest->users;

        }
        $data['status'] = 'success';
        $data['message'] = '';
        $data['data'] = $final_data;
        return $data;
    }
    public function getPlayingTeam(Request $request)
    {

        $data['status'] = 'success';
        $data['message'] = '';
        $data['data'] = null;
        if(isset($request->contest_id)){
            $matches = Contest::find($request->contest_id)->matches;
            foreach ($matches as $key => $match){
                $data['data'][$key]['match']= $match->toArray();
                $data['data'][$key]['match'][0]['team']= $match->team1()->select('id','sym_id','name')->first();
                $team_salary = 0;
                $team_count = 0;
                $team_players = Player::where('team_id','=',$match->team1->id)->whereIn('position',['Goalkeeper','Defender'])->get();
                foreach ($team_players as $player){
                    $salary = PlayerSalary::where('player_uid','=',$player->uID)->first();
                    if($salary != null) {
                        $team_count++;
                        $team_salary = $team_salary + $salary->salary;
                    }
                }
                $team_salary = round($team_salary/$team_count,2);
                $data['data'][$key]['match'][0]['team']['salary']= $team_salary;
                $data['data'][$key]['match'][0]['players'] = Player::select('id','name','position','uID')
                    ->where('team_id','=',$match->team1->id)->whereNotIn('position',['Goalkeeper','Defender'])->get();
                foreach ($data['data'][$key]['match'][0]['players'] as $k => $player){
                    if(UserPick::where('contest_id','=',$request->contest_id)->where('player_id','=',$player->id)->count() > 0){
                        $data['data'][$key]['match'][0]['players'][$k]['is_taken'] = true;
                    }else{
                        $data['data'][$key]['match'][0]['players'][$k]['is_taken'] = false;
                    }
                    $salary = PlayerSalary::where('player_uid','=',$player->uID)->first();
                    if($salary != null){
                        $data['data'][$key]['match'][0]['players'][$k]['salary'] = (float)$salary->salary;
                    }else{
                        $data['data'][$key]['match'][0]['players'][$k]['salary'] = 2000.00;
                    }
                }

                $data['data'][$key]['match'][1]['team']= $match->team2()->select('id','sym_id','name')->first();
                $team_players = Player::where('team_id','=',$match->team1->id)->whereIn('position',['Goalkeeper','Defender'])->get();
                $team_count = 0;
                foreach ($team_players as $player){
                    $salary = PlayerSalary::where('player_uid','=',$player->uID)->first();
                    if($salary != null) {
                        $team_count++;
                        $team_salary = $team_salary + $salary->salary;
                    }
                }
                $team_salary = round($team_salary/$team_count,2);
                $data['data'][$key]['match'][1]['team']['salary']= $team_salary;
                $data['data'][$key]['match'][1]['players'] = Player::select('id','name','position')
                    ->where('competition_id','=',$match->competiton_id)
                    ->where('team_id','=',$match->team2->id)->whereNotIn('position',['Goalkeeper','Defender'])->get();

                foreach ($data['data'][$key]['match'][1]['players'] as $k => $player){
                    if(UserPick::where('contest_id','=',$request->contest_id)->where('player_id','=',$player->id)->count() >0){
                        $data['data'][$key]['match'][1]['players'][$k]['is_taken'] = true;
                    }else{
                        $data['data'][$key]['match'][1]['players'][$k]['is_taken'] = false;
                    }
                    $salary = PlayerSalary::where('player_uid','=',$player->uID)->first();
                    if($salary != null){
                        $data['data'][$key]['match'][1]['players'][$k]['salary'] = $salary->salary;
                    }else{
                        $data['data'][$key]['match'][1]['players'][$k]['salary'] = "2000.00";
                    }
                }
            }
        }
        else{
            $data['data'] = Player::where('competition_id','=',$request->competition_id)
                ->where('team_id','=',$request->team_id)->api()->get();
        }
        return $data;
    }
    public function getPlayerDetail(Request $request,$id){
        $player = Player::find($id);
        if($player == null){
            $data['status'] = 'error';
            $data['message'] = 'No player found';
        }else{
            $data['status'] = 'success';
            $data['data'] = $player->toArray();
            $data['data']['team'] = Team::find($player->team_id);
            $player_lineups = PlayerLineup::where('competition_id','=',$player->competition_id)
                ->where('team_id','=',$player->team_id)->where('player_id','=',$player->id)->get();
            foreach ($player_lineups as $key => $player_lineup){
                $data['data']['previous_stats'][$key] = $player_lineup->toArray();
                if($player_lineup->match->team1->id == $player_lineup->team_id){
                    $data['data']['previous_stats'][$key]['opp_team'] = $player_lineup->match->team2;
                }else{
                    $data['data']['previous_stats'][$key]['opp_team'] = $player_lineup->match->team1;
                }
            }
        }
        return $data;
    }
}
