<?php

namespace App\Http\Controllers\Api;

use App\Award;
use App\AwardItem;
use App\Contest;
use App\Page;
use App\Player;
use App\PlayerSalary;
use App\User;
use App\UserAffiliate;
use App\UserProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\File;
use Symfony\Component\Console\Input\Input;
use Vinkla\Hashids\Facades\Hashids;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function getSettings(){
        $data['lock_time'] = CNF_LOCKTIME;
        return $data;
    }
    public function postSettings(Request $request){
        $path_to_file = base_path('setting.php');
        $val  =		"<?php \n";
        $val .= 	"define('CNF_LOCKTIME',".(integer)$request->lock_time.");\n";
        $val .= 	"?>";
        file_put_contents($path_to_file,$val);
        $data['status']= 'success';
        return $data;
    }

    public function getUsers(Request $request)
    {
        $from = 0;
        $take = 50;
        if(isset($request->from)){
            $from = $request->from;
        }
        if(isset($request->take)){
            $take = $request->take;
        }
        $data['total'] = User::select('id')->where('role','!=',0)->count();
        $data['data']= User::where('role','!=',0)->take($take)->offset($from)->get();
        $data['status']= "success";
        return $data;
    }
    public function postBan(Request $request){
        $user = User::find($request->user_id);
        $note = $request->note;
        $user->status = $user->status==0?1:0;
        $user->status_note = $note;
        $user->save();
        $data['status']= "success";
        if($user->status==1){
            $data['message']= "User unbanned";
        }else{
            $data['message']= "User banned";
        }
        return $data;
    }
    public function getAwards(Request $request)
    {
        $from = 0;
        $take = 50;
        if(isset($request->from)){
            $from = $request->from;
        }
        if(isset($request->take)){
            $take = $request->take;
        }
        $data['total'] = Award::select('id')->count();
        $data['data']= Award::take($take)->offset($from)->with('items')->get();
        $data['status']= "success";
        return $data;
    }
    public function postAward(Request $request){
        if(Contest::where('award_id','=',$request->award_id)->where(function($q){
            $q->where('status','=','Created')->orWhere('status','=','Ongoing');
        })->count()){
            $data['status']= "error";
            $data['message']= "Award in use by ongoing contest.";
            return $data;
        }
        $award = new Award();
        if(isset($request->award_id)){
            $award = Award::find($request->award_id);
        }
        $award->name= $request->name;
        $award->min_entrants= $request->min_entrants;
        $award->save();

        $award_items = json_decode($request->award_items);

        AwardItem::where('award_id','=',$award->id)->delete();

        foreach ($award_items as $item){
            $award_item = new AwardItem();
            $award_item->award_id = $award->id;
            $award_item->rank = $item->rank;
            $award_item->share = isset($item->share)?$item->share:"";
            $award_item->reward = isset($item->reward)?$item->reward:"";
            $award_item->type = $item->type;
            $award_item->save();
        }
        $data['status']= "success";
        $data['message']= "Award and Award items added.";
        return $data;
    }
    public function deleteAward(Request $request){
        if(Contest::where('award_id','=',$request->award_id)->where(function($q){
            $q->where('status','=','Created')->orWhere('status','=','Ongoing');
        })->count()){
            $data['status']= "error";
            $data['message']= "Award in use by ongoing contest.";
            return $data;
        }
        $award = Award::find($request->award_id);
        $award->delete();
        $data['status']= "success";
        $data['message']= "Award and Award items deleted.";
        return $data;
    }
    public function getPlayers(Request $request)
    {
        $from = 0;
        $take = 30;
        if(isset($request->from)){
            $from = $request->from;
        }
        if(isset($request->take)){
            $take = $request->take;
        }
        $players = PlayerSalary::take($take)->offset($from)->get();
        foreach ($players as $key=>$player_salary){
            $player = Player::where('uID','=',$player_salary->player_uid)->first();
            $players[$key]['stats'] = $player->stats;
            $players[$key]['team'] = $player->team;
        }
        $data['total'] = PlayerSalary::select('id')->count();
        $data['data']=$players;
        $data['status']= "success";
        return $data;
    }
    public function postPlayer(Request $request)
    {
        $player = PlayerSalary::where('player_uid','=',$request->player_uid)->first();
        $player->salary = $request->salary;
        $player->save();
        $data['status']= "success";
        $data['message']= "Player salary updated";
        return $data;
    }
    public function getUploads(Request $request)
    {
        $uploads = File::all();
        foreach ($uploads as $upload){
            $data['data'][$upload->name] = $upload->path;
        }
        $data['status']= "success";
        return $data;
    }
    public function postUpload(Request $request){
        $file = new File();

        if(File::where('name','=',$request->name)->count()){
            $file = File::where('name','=',$request->name)->first();
        }
        $file->name = $request->name;
        $extension = $request->image->extension();
        $file->path = $request->image->storeAs('uploads',$request->name);
        $file->save();
        $data['status']= "success";
        $data['message']= "File Uploaded";
        return $data;
    }
    public function postPage(Request $request){
        $page = new Page();
        $page->name = $request->name;
        $page->text = $request->text;
        $page->save();
        $data['status']= "success";
        $data['message']= "Page content saved";
        return $data;
    }
    public function cancelContest(Request $request)
    {
        $contest = Contest::find($request->contest_id);
        $contest->status = "Cancelled";
        $contest->save();
        $data['status']= "success";
        $data['message']= "Contest cancelled";
        return $data;
    }
}
