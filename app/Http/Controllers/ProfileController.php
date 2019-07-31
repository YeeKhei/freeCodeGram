<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;

class ProfileController extends Controller
{
    public function index(User $user)
    {
        $follows=(auth()->user())? auth()->user()->following->contains($user->id) : false;

        //load and save caches
        $postCount=Cache::remember(
            'count.posts.'.$user->id,
            now()->addSeconds(30),// adjust the caches time remain
            function()use($user){
            return $user->posts()->count();
        });

        $followersCount=Cache::remember(
            'count.posts.'.$user->id,
            now()->addSeconds(30),
            function()use($user){
                return $user->profile->followers->count();
            });
        $followingCount=Cache::remember(
            'count.posts.'.$user->id,
            now()->addSeconds(30),
            function()use($user){
                return $user->following->count();
            });

        return view('profiles.index',compact('user','follows','postCount','followersCount','followingCount'));
    }
    public function edit(User $user)
    {
        $this->authorize('update',$user->profile);

        return view('profiles.edit',compact('user'));
    }
    public function update(User $user)
    {
        $this->authorize('update',$user->profile);

        $data=request()->validate([
            'title'=>'required',
            'description'=>'required',
            'url'=>'url',
            'image'=>'',
        ]);

        if(request('image'))//sometimes if user want to change image
        {
            $imagePath=request('image')->store('profile','public');
            $image=\Intervention\Image\Facades\Image::make(public_path("storage/{$imagePath}"))->fit(1000,1000);
            $image->save();

            $ImageArray= ['image'=>$imagePath];
        }

        //array merger is use to merge all the data update into one section.
               auth()->user()->profile->update(array_merge(
            $data,
            $ImageArray??[]
        ));//update all information

        return redirect("/profile/{$user->id}");
    }
}
