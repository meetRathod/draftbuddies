<?php

namespace App\Http\Controllers\Api;

use App\Award;
use App\AwardItem;
use App\Contest;
use App\ContestEntrant;
use App\ContestInvite;
use App\Friend;
use App\Lineup;
use App\Mail\Invite;
use App\User;
use App\UserAffiliate;
use App\UserPick;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    function getUser(Request $request) {
        $data['status'] = 'success';
        $data['data'] =  $request->user();
        return $data;
    }
    function changePassword(Request $request) {
        $user = $request->user();
        if(bcrypt($request->old_password) !=$user->password){
            $data['status'] = 'error';
            $data['message'] =  "Old password doesn't match";
            return $data;
        }else{
            $user->password = bcrypt($request->new_password);
            $user->save();
            $data['status'] = 'success';
            $data['message'] =  "Password changed";
            return $data;
        }
    }
    function getAffiliateCode(Request $request) {
        $data['status'] = 'success';
        $data['data'] =  Hashids::encode($request->user()->id);
        return $data;
    }
    function getContests(Request $request) {
        $filter = $request->filter;
        if($filter != 'invites'){
            if($filter == 'live'){
                $status = ['Ongoing'];
            }elseif($filter == 'prev'){
                $status = ['Completed','Cancelled'];
            }elseif($filter == 'upcoming'){
                $status = ['Created','Locked'];
            }else{
                $status = ['Ongoing','Completed','Cancelled','Created','Locked'];
            }
            $contests_created = Contest::where('user_id','=',$request->user()->id)
                ->whereIn('status',$status)->with('award')->get();

            $contests_joined = $request->user()->contests()->whereIn('status',$status)->with('award')->get();

            $data['data'] = $contests_created->merge($contests_joined);
        }else{
            $contest_ids = ContestInvite::where('to_id','=',$request->user()->id)->pluck('contest_id')->toArray();
            $data['data'] = Contest::whereIn('id',$contest_ids)->with('award')->get();
        }


        foreach ($data['data'] as $key => $contest){
            $data['data'][$key]['entrant_count'] = ContestEntrant::where('contest_id','=',$contest->id)->count();

            if(ContestEntrant::where('contest_id','=',$contest->id)->where('user_id','=',$request->user()->id)->first() == null){
                $data['data'][$key]['is_user_entrant'] = false;
            }else{
                $data['data'][$key]['is_user_entrant'] = true;
                $data['data'][$key]['user_picks'] = UserPick::where('contest_id','=',$contest->id)->where('user_id','=',$request->user()->id)
                    ->pluck('player_id');
                $teams = UserPick::where('contest_id','=',$contest->id)->where('user_id','=',$request->user()->id)->pluck('team_id')->toArray();
                $c = array_count_values($teams);
                $data['data'][$key]['team_id'] = array_search(max($c), $c);
            }
        }

        $data['status'] = 'success';
        return $data;
    }
    function postConfirmContest(Request $request){
        $user_id = $request->user()->id;
        $contest_id = $request->contest_id;
        if(ContestEntrant::where('contest_id','=',$contest_id)->where('user_id','=',$user_id)->first() != null){
            $contest_entrant = ContestEntrant::where('contest_id','=',$contest_id)->where('user_id','=',$user_id)->first();
            $contest_entrant->is_active = 1;
            $contest_entrant->save();
        }
    }
    function getLineups(Request $request) {
        $data['status'] = 'success';
        $data['data'] = Lineup::where('user_id','=',$request->user()->id)->with('picks')->api()->get();
        return $data;
    }
    function getFriends(Request $request) {
        $data['status'] = 'success';
        $requested = $request->user()->myFriends(function($q){
            $q->where('status','=',1);
        })->get();
        $accepted = $request->user()->friendsOf(function($q){
            $q->where('status','=',1);
        })->get();
        $data['data'] = $requested->merge($accepted)->unique();
        return $data;
    }
    function postRequest(Request $request) {

        if(Friend::where('user_id','=',$request->user()->id)->where('friend_id','=',$request->friend_id)->count() == 0 && Friend::where('user_id','=',$request->friend_id)->where('friend_id','=',$request->user()->id)->count() == 0){
            $friend = new Friend();
            $friend->user_id = $request->user()->id;
            $friend->friend_id = $request->friend_id;
            $friend->save();
            $data['message'] = 'Friend request sent';

        }else{
            $data['message'] = 'Already as friend or request pending.';
        }

        $data['status'] = 'success';

        return $data;
    }
    function removeRequest(Request $request) {
        Friend::where('user_id','=',$request->user()->id)->where('friend_id','=',$request->friend_id)->delete();
        Friend::where('friend_id','=',$request->user()->id)->where('user_id','=',$request->friend_id)->delete();
        $data['message'] = 'Removed friend/friend request';
        $data['status'] = 'success';
        return $data;
    }
    function getPendingRequests(Request $request) {
        $data['status'] = 'success';
        $pending = Friend::where('friend_id','=',$request->user()->id)->where('status','=',0)->with('user')->get();
        $data['data'] = $pending;
        return $data;
    }
    function postAcceptRequest(Request $request) {
        $friend = Friend::find($request->request_id);
        $friend->status = 1;
        $friend->save();
        $data['message'] = 'Friend request accepted';
        $data['status'] = 'success';
        return $data;
    }
    function getInvitedContests(Request $request) {
        $contest_ids = ContestInvite::where('to_id','=',$request->user()->id)->pluck('contest_id')->toArray();
        $data['data'] = Contest::whereIn('id',$contest_ids)->with('award')->get();
        foreach ($data['data'] as $key => $contest){
            $from_id = ContestInvite::where('to_id','=',$request->user()->id)->where('contest_id','=',$contest->id)->first()->from_id;
            $data['data'][$key]['invited_by'] = User::find($from_id);
        }
        $data['status'] = 'success';
        return $data;
    }
    function postInvite(Request $request) {
        $email = $request->email;
        $user = $request->user();
        Mail::to($email)->send(new Invite($user));
        $data['status'] = 'success';
        $data['message'] = 'User Invited';
        return $data;
    }
    function removeInvitedContest(Request $request) {
        ContestInvite::where('to_id','=',$request->user()->id)->where('contest_id','=',$request->contest_id)->delete();
        $data['status'] = 'success';
        $data['message'] = 'Invitation Removed';
        return $data;
    }
    function getRewards(Request $request){
        $entries = ContestEntrant::where('user_id','=',$request->user()->id)->where('is_active','=',1)->where('award_claimed','=',0)->get();
        foreach ($entries as $key => $entry){
            $contest =Contest::find($entry->contest_id);
            if($contest->status =='Cancelled'){
                $data['data'][$key] = $entry;
                $data['data'][$key]['type'] = "return";
                $data['data'][$key]['amount'] = $contest->entry_fee * 0.9;
            }else{
                $data['data'][$key] = $entry;
                $award_item = AwardItem::where('award_id','=',$contest->award_id)
                    ->where('rank','=',(integer)$entry->rank)->first();
                $data['data'][$key]['type'] = $award_item->type;
            }
        }
        $data['status'] = 'success';
        return $data;
    }

}
