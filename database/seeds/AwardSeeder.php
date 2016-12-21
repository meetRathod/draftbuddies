<?php

use Illuminate\Database\Seeder;

class AwardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $award = new \App\Award();
        $award->name = 'Top 1';
        $award->min_entrants = 2;
        $award->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 1;
        $award_item->share = 1;
        $award_item->type = 'share';
        $award_item->save();

        $award = new \App\Award();
        $award->name = 'Top 3';
        $award->min_entrants = 3;
        $award->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 1;
        $award_item->share = 0.5;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 2;
        $award_item->share = 0.35;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 3;
        $award_item->share = 0.15;
        $award_item->type = 'share';
        $award_item->save();

        $award = new \App\Award();
        $award->name = 'Top 5';
        $award->min_entrants = 5;
        $award->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 1;
        $award_item->share = 0.4;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 2;
        $award_item->share = 0.3;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 3;
        $award_item->share = 0.15;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 4;
        $award_item->share = 0.10;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 5;
        $award_item->share = 0.05;
        $award_item->type = 'share';
        $award_item->save();

        $award = new \App\Award();
        $award->name = 'Top 10';
        $award->min_entrants = 10;
        $award->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 1;
        $award_item->share = 0.3;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 2;
        $award_item->share = 0.2;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 3;
        $award_item->share = 0.15;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 4;
        $award_item->share = 0.10;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 5;
        $award_item->share = 0.06;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 6;
        $award_item->share = 0.06;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 7;
        $award_item->share = 0.04;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 8;
        $award_item->share = 0.04;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 9;
        $award_item->share = 0.025;
        $award_item->type = 'share';
        $award_item->save();
        $award_item = new \App\AwardItem();
        $award_item->award_id = $award->id;
        $award_item->rank = 10;
        $award_item->share = 0.025;
        $award_item->type = 'share';
        $award_item->save();
    }
}
