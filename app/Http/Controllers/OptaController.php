<?php

namespace App\Http\Controllers;

use App\Competition;
use App\Contest;
use App\ContestMatch;
use App\EventPoint;
use App\Match;
use App\Player;
use App\PlayerLineup;
use App\PlayerSalary;
use App\PlayerStat;
use App\Team;
use App\TeamStat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Events\ScoreUpdated;

use App\Http\Requests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OptaController extends Controller
{
    //
    public function postIndex(Request $request){
        $headers = $request->headers->all();
        $post_data = file_get_contents('php://input') ;
        $posts = array (
            'feedType' => isset($headers['x-meta-feed-type']) ? $headers['x-meta-feed-type'][0] : '',
            'feedParameters' => isset($headers['x-meta-feed-parameters']) ? $headers['x-meta-feed-parameters'][0] : '',
            'defaultFilename' => isset($headers['x-meta-default-filename']) ? $headers['x-meta-default-filename'][0] : '',
            'deliveryType' => isset($headers['x-meta-game-id']) ? $headers['x-meta-game-id'][0] : '',
            'messageDigest' => md5($post_data),
            'competitionId' => isset($headers['x-meta-competition-id']) ? $headers['x-meta-competition-id'][0] : '',
            'seasonId' => isset($headers['x-meta-season-id']) ? $headers['x-meta-season-id'][0] : '',
            'gameId' => isset($headers['x-meta-game-id']) ? $headers['x-meta-game-id'][0] : '',
            'gameSystemId' => isset($headers['x-meta-gamesystem-id']) ? $headers['x-meta-gamesystem-id'][0] : '',
            'matchday' => isset($headers['x-meta-matchday']) ? $headers['x-meta-matchday'][0] : '',
            'awayTeamId' => isset($headers['x-meta-away-team-id'])?$headers['x-meta-away-team-id'][0]:'',
            'homeTeamId' => isset($headers['x-meta-home-team-id']) ? $headers['x-meta-home-team-id'][0] : '',
            'gameStatus' => isset($headers['x-meta-game-status']) ? $headers['x-meta-game-status'][0] : '',
            'language' => isset($headers['x-meta-language']) ? $headers['x-meta-language'][0] : '',
            'productionServer' => isset($headers['x-meta-production-server']) ? $headers['x-meta-production-server'][0] : '',
            'productionServerTimeStamp' => isset($headers['x-meta-production-server-timestamp']) ? $headers['x-meta-production-server-timestamp'][0] : '',
            'productionServerModule' => isset($headers['x-meta-production-server-module']) ? $headers['x-meta-production-server-module'][0] : '',
            'mimeType' => 'text/xml',
            'encoding' => isset($headers['x-meta-encoding']) ? $headers['x-meta-encoding'] : '',
            'content' => $post_data
        );

        Storage::disk('local')->put('Feeds'.'/'.$posts['feedType'].'_'.$posts['defaultFilename'],$post_data);

        try{
        $content = simplexml_load_string($posts['content']);

        if($posts['feedType'] == 'F40' || $posts['feedType'] == 'f40'){

            $competition_data = $content->SoccerDocument->attributes();
            if(Competition::where('season_id','=',$competition_data->season_id)->where('code','=',$competition_data->competition_code)->count()){
                $competition = Competition::where('season_id','=',$competition_data->season_id)->where('code','=',$competition_data->competition_code)->first();
            }else{
                $competition = new Competition();
            }
            $competition->type = (string)$competition_data->Type;
            $competition->code = (string)$competition_data->competition_code;
            $competition->sys_id = (string)$competition_data->competition_id;
            $competition->name = (string)$competition_data->competition_name;
            $competition->season_id = (string)$competition_data->season_id;
            $competition->season_name = (string)$competition_data->season_name;
            $competition->save();

            foreach ($content->SoccerDocument->Team as $team_data){
                $team = Team::where('competition_id','=',$competition->id)->where('uID','=',(string)$team_data->attributes()->uID)->first();
                if($team == null){
                    $team = new Team();
                }
                $team->competition_id = $competition->id;
                $team->uID = (string)$team_data->attributes()->uID;
                $team->sym_id = (string)$team_data->SYMID;
                $team->name = (string)$team_data->Name;
                $team->city = isset($team_data->attributes()->city)?(string)$team_data->attributes()->city:"";
                $team->country = isset($team_data->attributes()->country)?(string)$team_data->attributes()->country:"";
                $team->web_address = isset($team_data->attributes()->web_address)?(string)$team_data->attributes()->web_address:"";
                $team->save();

                foreach ($team_data->Player as $player_data) {
                    $player = Player::where('competition_id','=',$competition->id)
                        ->where('uID','=',(string)$player_data->attributes()->uID)->first();
                    if($player == null){
                        $player = new Player();
                    }
                    $player->competition_id = $competition->id;
                    $player->team_id = $team->id;
                    $player->uID = (string)$player_data->attributes()->uID;
                    $player->loan = isset($player_data->attributes()->loan)?(string)$player_data->attributes()->loan:0;
                    $player->name = (string)$player_data->Name;
                    $player->position = (string)$player_data->Position;

                    $statArr = array();
                    foreach ($player_data->Stat as $key => $stat){
                        $statArr[$key][(string)$stat->attributes()->Type] = (string)$stat;
                    }
                    if($player->position == 'Midfielder'){
                        if(in_array($statArr['Stat']['real_position'],['Midfielder','Attacking Midfielder','Winger'])){
                            $player->position = 'Offensive Midfielder';
                        }else{
                            $player->position = 'Defensive Midfielder';
                        }
                    }
                    if((string)$player_data->Position == 'Forward'){
                        $player->position = 'Striker';
                    }
                    $player->stats = json_encode($statArr);
                    $player->save();
                }
            }
        }

        if($posts['feedType'] == 'F1' || $posts['feedType'] == 'f1'|| $posts['feedType'] == 'F01' || $posts['feedType'] == 'f01'){
            $competition_data = $content->SoccerDocument->attributes();

            $competition = Competition::where('season_id','=',(string)$competition_data->season_id)
                ->where('code','=',(string)$competition_data->competition_code)->first();

            if($competition ==null){
                $competition = new Competition();
                $competition->type = (string)$competition_data->Type;
                $competition->code = (string)$competition_data->competition_code;
                $competition->sys_id = (string)$competition_data->competition_id;
                $competition->name = (string)$competition_data->competition_name;
                $competition->season_id = (string)$competition_data->season_id;
                $competition->season_name = (string)$competition_data->season_name;
                $competition->save();
            }
            foreach ($content->SoccerDocument->MatchData as $match_data){
                $match = Match::where('uID','=',(string)$match_data->attributes()->uID)->first();
                if ($match == null){
                    $match = new Match();
                }
                $match->competition_id = $competition->id;
                $match->uID = (string)$match_data->attributes()->uID;
                $match->group_name = (string)$match_data->MatchInfo->attributes()->GroupName;
                $match->match_day = (string)$match_data->MatchInfo->attributes()->MatchDay;
                $match->match_type = (string)$match_data->MatchInfo->attributes()->MatchType;
                $match->round_number = (string)$match_data->MatchInfo->attributes()->RoundNumber;
                $match->round_type = (string)$match_data->MatchInfo->attributes()->RoundType;

                foreach ($match_data->Stat as $stat){
                    if((string)$stat->attributes()->Type == 'Venue'){
                        $match->venue = (string)$stat;
                    }
                    if((string)$stat->attributes()->Type == 'City'){
                        $match->city = (string)$stat;
                    }
                }
                $match->home_team = "";
                $match->team_1 = (string)$match_data->TeamData[0]->attributes()->TeamRef;
                if((string)$match_data->TeamData[0]->attributes()->Side == 'Home'){
                    $match->home_team = (string)$match_data->TeamData[0]->attributes()->TeamRef;
                }
                $match->team_2 = (string)$match_data->TeamData[1]->attributes()->TeamRef;
                if((string)$match_data->TeamData[1]->Side == 'Home'){
                    $match->home_team = (string)$match_data->TeamData[1]->attributes()->TeamRef;
                }
                $match->scheduled_on = (string)$match_data->MatchInfo->Date.' '.(string)$match_data->MatchInfo->TZ;
                $match->scheduled_on  = date('Y-m-d H:i:s',strtotime($match->scheduled_on));
                $match->entry_type = (string)$match_data->MatchInfo->attributes()->Period;
                $match->save();
            }


        }

        if($posts['feedType'] == 'F30' || $posts['feedType'] == 'f30'){
            $team_stats = TeamStat::where('competition','=',(string)$content->attributes()->competition_name)
                ->where('team_uid','=',(string)$content->Team->attributes()->id)->first();
            if($team_stats == null){
                $team_stats = new TeamStat();
            }

            $stats = array();
            foreach ($content->Team->Stat as $stat){
                $stats[str_replace(' ', '_', (string)$stat->attributes()->name)] = (string)$stat;
            }
            $team_stats->competition = (string)$content->attributes()->competition_name;
            $team_stats->team_uid = (string)$content->Team->attributes()->id;
            $team_stats->last_stats = json_encode($stats);
            $team_stats->save();

            foreach ($content->Team->Player as $player_data){
                $stats = array();

                foreach ($player_data->Stat as $stat){
                    $stats[str_replace(' ', '_', (string)$stat->attributes()->name)] = (string)$stat;
                }

                $player_stat = PlayerStat::where('competition','=',(string)$content->attributes()->competition_name)
                    ->where('player_uid','=',(string)$player_data->attributes()->player_id)->first();
                if($player_stat == null){
                    $player_stat = new PlayerStat();
                }

                $player_stat->competition = (string)$content->attributes()->competition_name;
                $player_stat->player_uid = (string)$player_data->attributes()->player_id;
                $player_stat->last_stats = json_encode($stats);
                $player_stat->save();
            }
        }

        if($posts['feedType'] == 'F9' || $posts['feedType'] == 'f9' || $posts['feedType'] == 'F09' || $posts['feedType'] == 'f09'){

//                LOG : F9 Feeds
            $log  = "Time: ".date("F j, Y, g:i a").PHP_EOL.
                "FileName: ".$posts['defaultFilename'].PHP_EOL.
                "Type: ".$content->SoccerDocument->attributes()->Type.PHP_EOL.
                "-------------------------".PHP_EOL;
            file_put_contents(storage_path('/app/feedlogs/').'f9_log_'.date("j.n.Y").'.txt', $log, FILE_APPEND);


//                GET Competiton
            $competition_data['name'] = (string)$content->SoccerDocument->Competition->Name;
            foreach ($content->SoccerDocument->Competition->Stat as $key => $stat) {
                $competition_data[(string)$stat->attributes()->Type] = (string)$stat;
            }

            $competition = Competition::where('name','=',$competition_data['name'])
                ->where('season_id','=',$competition_data['season_id'])->first();

            if($competition!=null){
                $teams_data[0] = $content->SoccerDocument->MatchData->TeamData[0];
                $teams_data[1] = $content->SoccerDocument->MatchData->TeamData[1];
                $winner = null;
                if($content->SoccerDocument->attributes()->Type=='Result'){
                    if($content->SoccerDocument->MatchData->Result && $content->SoccerDocument->MatchData->Result->attributes()->Winner){
                        $winner = (string)$content->SoccerDocument->MatchData->Result->attributes()->Winner;
                    }
                }
                foreach ($teams_data as $team_data){
//                        GET Match
                    $match = Match::where('competition_id','=',$competition->id)->where(function($query) use($team_data){
                        $query->where('team_1','=',(string)$team_data->attributes()->TeamRef)
                            ->orWhere('team_2','=',(string)$team_data->attributes()->TeamRef);
                    })->where('match_day','=',$competition_data['matchday'])->first();

                    if($match!=null){
//                            UPDATE : match status
                        $match->status = (string)$content->SoccerDocument->MatchData->MatchInfo->attributes()->Period;
                        $match->save();

                        
//                            UPDATE : Contest status
                        $contests = $match->contests;
                        foreach ($contests as $contest){
                            $last = $contest->matches(function($q){
                                $q->orderBy('scheduled_on','desc');
                            })->first();

                            if($contest->status != 'Cancelled'){
                                if($last->id == $match->id){
                                    if($match->status == 'FullTime'){
                                        $contest->status = 'Completed';
                                    }elseif($match->status == 'PreMatch'){
                                        //
                                    }else{
                                        $contest->status = 'Ongoing';
                                    }
                                }
                            }
                            $contest->save();
                        }

                        $playerslineup_data = array();
                        foreach ($team_data->PlayerLineUp->MatchPlayer as $key => $data){
                            $stats = array();
                            foreach($data->Stat as $k => $item){
                                $stats[(string)$item->attributes()->Type] = (string)$item;
                            }
                            $player = Player::where('competition_id','=',$competition->id)
                                ->where('uID','=',$data->attributes()->PlayerRef)->first();

                            if($player != null){
//                          Calculate : player scores
                                $event_occurence =[
                                    'assist' => 0,
                                    'atm_goal' => 0,
                                    'missed_penalty' =>0,
                                ];

                                $assist = ['goal_assist','assist_blocked_shot','assist_attempt_saved','assist_post','assist_own_goal','assist_penalty_won'];
                                $atm_goal =['ontarget_scoring_att','clearance_off_line','hit_woodwork'];
                                $missed_penalty =['att_pen_mis','att_pen_post','att_pen_target'];

                                if($player->position == 'Goalkeeper' || $player->position == 'Defender'){
                                    $total_points = 20;
                                }else{
                                    $total_points = 0;
                                }
                                foreach ($stats as $event => $stat){
                                    if(in_array($event, $assist)){
                                        $event_occurence['assist'] = $event_occurence['assist']+1;
                                    }
                                    if(in_array($event, $atm_goal)){
                                        $event_occurence['atm_goal'] = $event_occurence['atm_goal']+1;
                                    }
                                    if(in_array($event, $missed_penalty)){
                                        $event_occurence['missed_penalty'] = $event_occurence['missed_penalty']+1;
                                    }
                                    $points = 0;
                                    if (array_key_exists($event,$event_occurence) &&  $event_occurence[$event] >1){
//                                            Do nothing
                                    }else{
                                        $event_point = EventPoint::where('position','=',$player->position)->where('event','=',$event)->first();
                                        if($event_point != null){
                                            if($event == 'red_card' && array_key_exists('second_yellow',$stats)){
//                                                Do nothing
                                            }elseif($event == 'goals_conceded'){
                                                switch($stat){
                                                    case 1: $points = 10;
                                                        break;
                                                    case 2: $points = 0;
                                                        break;
                                                    case 3: $points = -10;
                                                        break;
                                                    default: $points = -20;
                                                        break;
                                                }
                                            }else{
                                                $points = $event_point->point;
                                                $points = $points * $stat;
                                            }
                                        }
                                    }
                                    $total_points = $total_points + $points;
                                }
                                $playerslineup_data[$key][(string)$data->attributes()->PlayerRef] = $stats;
                                $playerslineup_data[$key][(string)$data->attributes()->PlayerRef]['position'] = (string)$data->attributes()->Position;
                                $playerslineup_data[$key][(string)$data->attributes()->PlayerRef]['shirt_number'] = (string)$data->attributes()->ShirtNumber;
                                $playerslineup_data[$key][(string)$data->attributes()->PlayerRef]['status'] = (string)$data->attributes()->Status;

                                $playerslineup_data[$key][(string)$data->attributes()->PlayerRef]['total_points'] = $total_points;
                            }
                        }

//                            UPDATE : Player Lineup

                        $team = Team::where('competition_id','=',$competition->id)
                            ->where('uID','=',(string)$team_data->attributes()->TeamRef)->first();

                        foreach($playerslineup_data['MatchPlayer'] as $uID => $playerlineup_data){
                            $player = Player::where('competition_id','=',$competition->id)->where('uID','=',$uID)->first();
                            if($player != null){
                                $player_lineup = PlayerLineup::where('match_id','=',$match->id)->where('team_id','=',$team->id)
                                    ->where('player_id','=',$player->id)->first();

                                if($player_lineup == null){
                                    $player_lineup = new PlayerLineup();
                                }
                                $player_lineup->competition_id = $competition->id;
                                $player_lineup->match_id = $match->id;
                                $player_lineup->team_id = $team->id;
                                $player_lineup->player_id = $player->id;
                                $player_lineup->position = $player->position;
                                $player_lineup->shirt_number = $playerlineup_data['shirt_number'];
                                $player_lineup->att_pen_target = isset($playerlineup_data['att_pen_target'])?$playerlineup_data['att_pen_target']:0;
                                $player_lineup->goals = isset($playerlineup_data['goals'])?$playerlineup_data['goals']:0;
                                $player_lineup->goals_assist = isset($playerlineup_data['goals_assist'])?$playerlineup_data['goals_assist']:0;
                                $player_lineup->accurate_pass = isset($playerlineup_data['accurate_pass'])?$playerlineup_data['accurate_pass']:0;
                                $player_lineup->yellow_card = isset($playerlineup_data['yellow_card'])?$playerlineup_data['yellow_card']:0;
                                if(isset($playerlineup_data['red_card']) && !isset($playerlineup_data['second_yellow'])) {
                                    $player_lineup->red_card = $playerlineup_data['red_card'];
                                }else{
                                    $player_lineup->red_card = 0;
                                }
                                $player_lineup->points = $playerlineup_data['total_points'];
                                $player_lineup->save();
                                if($winner == $team->uID){
                                    $player_lineup->points = $player_lineup->points + 3;
                                    $player_lineup->save();
                                }

                                if($content->SoccerDocument->attributes()->Type=='Result' && $match->status == 'FullTime'){
                                    $player_salary = PlayerSalary::where('player_uid','=',$player->uID)->first();
                                    if($player_salary !=null){
                                        $player_salary->points = $player_salary->points+$player_lineup->points;
                                        if($player_salary->matches > 0){
                                            $player_salary->matches = $player_salary->matches+1;
                                        }else{
                                            $player_salary->matches = 1;
                                        }
                                        $avg_points = $player_salary->points / $player_salary->matches;
                                        $player_salary->salary = $player_salary->salary+ (50*$avg_points);
                                        $player_salary->save();
                                    }
                                }
                                if($match->status == 'PreMatch'){
                                    if($player->id == $player_lineup->player_id){
                                        $player->is_playing = 'Finalised';
                                    }else{
                                        $player->is_playing = 'Not playing';
                                    }
                                    $player->save();
                                }
                            }
                        }

                        $defense_lineups = PlayerLineup::where('match_id','=',$match->id)->where('team_id','=',$team->id)
                            ->where(function($q){
                                $q->where('position','=','Goalkeeper')->orWhere('position','=','Defender');
                            })->get();

                        $defense_points = 0;

                        foreach ($defense_lineups as $defense_lineup){
                            $defense_points = $defense_points+ $defense_lineup->points;
                        }

                        foreach ($defense_lineups as $defense_lineup){
                            $defense_lineup->points = $defense_points/count($defense_lineups);
                            $defense_lineup->save();
                        }

                    }
                };
            }
        }
        }
        catch (\Exception $e){
        }
//        shell_exec('sh '.storage_path('app/sort.sh'));
        return "Success";
    }
}