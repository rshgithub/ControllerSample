<?php

namespace App\Models;

use App\Models\later\Room_Reservation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Overtrue\LaravelFavorite\Traits\Favoriteable;

class Hotel_Room extends Model
{
    use HasFactory, Favoriteable, SoftDeletes;

    protected $table = 'hotel_rooms';
    protected $fillable = ['id', 'name', 'capacity', 'hotel_id', 'details', 'price_per_night', 'available_rooms', 'has_offer'];
    protected $appends = ['room_hotel_name'];
    protected $hidden = ['hotel', 'created_at', 'updated_at', 'deleted_at'];


    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function orderItem()
    {
        return $this->hasMany(OrderItem::class, 'room_id');
    }

    public function getRoomPicsAttribute()
    {
        $media = [];
        $room_media = $this->media;
        if ($room_media->count()) {
            foreach ($room_media as $roomMedia) {
                array_push($media, url('/storage/' . $roomMedia->name));
            }
        }
        return $media;
    }

    public function getDefaultImgAttribute(){
        return url('/add_photo.png');
    }

    public function getRoomImagesAttribute()
    {
        $media = [];
        if ($this->media->count()) {
            foreach ($this->media as $roomMedia) {
                array_push($media, url('/storage/' . $roomMedia->name));
            }
        } else {
            array_push($media, url('/add_photo.png'),url('/add_photo.png'));
        }
        return $media;
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id', 'id')->withDefault(['name' => 'no related hotel']);
    }

    public function getRoomHotelNameAttribute()
    {
        return $this->hotel ? $this->hotel->name : 'hotel not found';
    }

    public function getRoomHotelImageAttribute()
    {
        return $this->hotel ? $this->hotel->hotel_image : 'hotel not found';
    }

    public function getFavsCountAttribute()
    {
        return $this->favorites()->count();
    }

    function getIsFavoritedByUserAttribute()
    {
        if (auth()->check()) {
            return auth()->user()->hasFavorited($this);
        } else {
            return false;
        }
    }

    //----------------------------------- control panel -----------------------------

    public function getRoomDisplayDataAttribute(){
        return [
            'id' => ' <label style=" font-weight: bold ;">' . $this->id . '</label>',
            'name' => ' <label style="font-weight: bold ;">' . $this->name . '</label>',
            'details' => $this->details_button,
            'capacity' => ' <label  style="font-weight: bold ; color: #4191bd">' . $this->capacity . '</label>',
            'has_offer' => $this->if_has_offer,
            'room_total_price_per_night' =>' <label  style="font-weight: bold ; color: #0a58ca">' . $this->room_total_price_per_night . ' $</label>',
            'available_rooms' => ' <label  style="font-weight: bold ; color: #a66dd3">' . $this->if_available_rooms . '</label>',
            'price_per_night' => ' <label  style="font-weight: bold ; color: #82c05a">' . $this->price_per_night . ' $</label>',
            'room_hotel_name' => ' <label  style="font-weight: bold ; color: #0a58ca">' . $this->room_hotel_name . '</label>',
            'hotel_room_images'=>$this->hotel_room_images,
            'tools'=>$this->edit.'&nbsp'.$this->delete
        ];
    }

    public function getRoomTotalPricePerNightAttribute(){
        return $this->price_per_night - $this->savings ;
    }

    public function getSavingsAttribute(){
        return ($this->price_per_night * $this->has_offer)/100;
    }


    /**
     * Scopes
     */
    public function scopeSearch($query,$searchWord)
    {
//        dd($searchWord);
        return $query->where('name', 'like', "%" . $searchWord . "%")
            ->orWhere('id', 'like', "%" . $searchWord . "%");
    }

    public function scopeFilterHotelName($query,$hotel_id){
        if($hotel_id){
            $query->where('hotel_id',$hotel_id);
        }else{
            return $query;
        }
    }

    public function getEditAttribute(){
        return '<button type="button" class="btn btn-warning" data-toggle="tooltip" data-placement="top" rel="tooltip" title="Edit '.$this->name.'" onclick="update(\''.route('hotel_rooms.edit',$this->id).'\',this)"><i class="ft-edit-2"></i></button>';
    }
    public function getDeleteAttribute(){
        return '<button type="button" class="btn btn-danger" data-toggle="tooltip" data-placement="top" rel="tooltip" title="Delete '.$this->name.'" onclick="deleteItem(\''.route('hotel_rooms.destroy',$this->id).'\')"><i class="ft-trash-2"></i></button>';
    }
    public function getHotelRoomImagesAttribute(){
        $room_pics = $this->room_pics;
        return '<img src="'.( $room_pics ? $room_pics[0] : $this->default_img).'" onclick="displayRoomImages(\''.route('hotel_rooms.displayRoomImages',$this->id).'\')" style="cursor:pointer;"/>';
    }
    public function getDetailsButtonAttribute(){
        return '<button type="button" class="btn btn-outline-success" data-toggle="tooltip" data-placement="top" rel="tooltip" title="Display '.$this->name.' Details " onclick="displayRoomDetails(\''.route('hotel_rooms.displayRoomDetails',$this->id).'\')"></i>more details</button>';
    }

    public function getIfHasOfferAttribute()
    {
        if ($this->has_offer == 0)
            return ' <label style="font-weight: bold ;" class="text-danger">' . $this->has_offer . '%</label>';
        else
            return ' <label  style="font-weight: bold ;" class="text-success">'. $this->has_offer .'%<i class="mdi mdi-arrow-down"></label>';
    }

    public function getIfAvailableRoomsAttribute()
    {
        if ($this->available_rooms == 0)
            return ' <label style="font-weight: bold ;" class="text-danger">' . $this->available_rooms . '</label>';
        else
            return ' <label  style="font-weight: bold ;" class="text-success">'. $this->available_rooms .'</label>';
    }

    //---------------------------------------------------------------------------------------------------------

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($hotel_room) {
            $hotel_room->media()->delete();
        });

        static::restored(function ($hotel_room) {
            $hotel_room->media()->restore();
        });

        static::deleting(function ($hotel_room) {
            $hotel_room->favorites()->delete();
        });

        static::deleting(function ($hotel_room) {
            $hotel_room->orderItem()->delete();
        });
    }

}
