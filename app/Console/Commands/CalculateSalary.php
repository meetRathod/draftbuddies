<?php

namespace App\Console\Commands;

use App\Competition;
use App\Player;
use App\PlayerLineup;
use App\PlayerSalary;
use App\PlayerStat;
use Illuminate\Console\Command;

class CalculateSalary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:salary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate salary for each player';

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
        $competition_ids =Competition::api()->pluck('id')->toArray();
        $player_lineups = PlayerLineup::whereIn('competition_id',$competition_ids)->get();
        foreach ($player_lineups as $player_lineup){
            $player_uid = Player::find($player_lineup->player_id)->uID;
            $player_salary = PlayerSalary::where('player_uid','=',$player_uid)->first();
            if($player_salary == null){
                $player_salary = new PlayerSalary();
                $player_salary->matches = 0;
                $player_salary->player_uid = $player_uid;
                $player_salary->points = 0;
                $player_salary->save();
            }
            $player_salary->points = $player_salary->points+$player_lineup->points;
            $player_salary->matches = $player_salary->matches+1;
            $avg_points = $player_salary->points / $player_salary->matches;
            $player_salary->salary = 2500+ (50*$avg_points);
            $player_salary->save();
        }
    }
}
