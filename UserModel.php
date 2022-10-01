<?php

namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Overtrue\LaravelFavorite\Traits\Favoriter;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens , Favoriter , SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id','name','image','email','role','address','password','fcm_token'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'password',
        'remember_token',
        'media',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function sendPasswordResetNotification($token)
    {

        $url = 'https://spa.test/reset-password?token=' . $token;

        $this->notify(new ResetPasswordNotification($url));
    }

    // relation with media table
    public function media(){
        return $this->morphOne(Media::class,'mediable');
    }

    public function getUserAvatarAttribute(){
        return $this->media ? ($this->media->name? url('/storage/'.$this->media->name) : url('/user.jpg')) : url('/user.jpg');
    }

    public function notifications(){
        return $this->hasMany(Notification::class,'user_id','id');
    }

    //not used
    public function getNotificationsCountAttribute()
    {
        return $this->notifications()->count();
    }

    public function getUserRoleAttribute(){
        if($this->role == 2) { return 'admin'; } else { return 'user'; }
    }

    public function getUserNotificationsAttribute(){
        return $this->hotel_orders ? $this->notifications->sortByDesc('created_at') : 0 ;
    }

    public function hotel_orders(){
        return $this->hasMany(HotelOrder::class,'user_id');
    }
    public function getUserHotelOrdersCountAttribute()
    {
        return $this->hotel_orders()->count();
    }
    public function getCurrentHotelOrderAttribute(){
        return $this->hotel_orders ? $this->hotel_orders->where('status',5)->first() : 0 ;
    }

    public function getCurrentHotelOrderItemsAttribute(){
        return $this->hotel_orders ? $this->current_hotel_order->order_items : 0 ;
    }

    public function getCurrentOrderHotelIdAttribute(){
        return $this->hotel_orders->count() ? $this->hotel_orders[0]->current_hotel_id : 0 ;
    }
    public function getCurrentOrderHotelNameAttribute(){
        return $this->hotel_orders->count() ? $this->hotel_orders[0]->current_hotel_name : 0 ;
    }

    public function getRegisteredAtAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    //----------------------------------- control panel -----------------------------

    public function getUsersDisplayDataAttribute(){
        return [
            'id' => ' <label style=" font-weight: bold ;">' . $this->id . '</label>',
            'name' => ' <label style="font-weight: bold ;" style="color: #367a0b">' . $this->name . '</label>',
            'user_role' => $this->if_user_role,
            'email' => ' <label style="font-weight: bold ;color: #0a58ca">' . $this->email . '</label>',
            'address' => '<label style="font-weight: bold ;">' . $this->address . '</label>',
            'user_avatar'=>'<img src="' . $this->user_avatar . '" class="avatar avatar-sm rounded-circle me-2" alt="spotify" >',
            'fcm_token' => $this->fcm_token,
            'registered_at'=> ' <label style=" font-weight: bold ; color: #7e40b0">' . $this->registered_at . '</label>',
            'tools'=>$this->delete
        ];
    }

    /**
     * Scopes
     */
    public function scopeSearch($query,$searchWord)
    {
        return $query->where('name', 'like', "%" . $searchWord . "%")
            ->orWhere('email', 'like', "%" . $searchWord . "%")
            ->orWhere('id', 'like', "%" . $searchWord . "%")
            ->orWhere('address', 'like', "%" . $searchWord . "%");
    }

    public function scopeFilterRole($query,$role)
    {
        if(!empty($role)){
            return $query->where('role', $role);

        }else{
            return $query;
        }
    }

    public function getDeleteAttribute(){
        if($this->id == Auth::user()->id){
            return null;
        }else{
            return '<button type="button" class="btn btn-danger" data-toggle="tooltip" data-placement="top" rel="tooltip" title="Delete '.$this->name.'" onclick="deleteItem(\''.route('users.destroy',$this->id).'\')"><i class="mdi mdi-delete-forever"></i></button>';
        }
    }

    public function getIfUserRoleAttribute()
    {
        if ($this->role == 2)
            return ' <label  class="badge badge-success">' . $this->user_role . '</label>';
        else
            return ' <label  class="badge badge-info" >' . $this->user_role . '</label>';
    }


    //---------------------------------------------------------------------------------------------------------

    public static function boot() {
        parent::boot();

        static::deleting(function($user) {
            $user->favorites()->delete();
        });

        static::restored(function($user) {
            $user->favorites()->restore();
        });

        static::deleting(function($user) {
            $user->notifications()->delete();
        });

        static::restored(function($user) {
            $user->notifications()->restore();
        });

        static::deleting(function($user) {
            $user->hotel_orders()->delete();
        });

        static::restored(function($user) {
            $user->hotel_orders()->restore();
        });

    }

}
