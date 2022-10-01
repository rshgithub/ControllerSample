<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory , SoftDeletes;
    protected $fillable = ['room_id','order_id','check_in','check_out','room_count'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function room(){
        return $this->belongsTo(Hotel_Room::class,'room_id','id')->withDefault(['name' => 'no related room']);
    }

    public function hotelOrder(){
        return $this->belongsTo(HotelOrder::class,'order_id','id')->withDefault(['name' => 'no related hotelOrder']);
    }

    public function getCreatedFromAttribute()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public function getTotalNightsAttribute(){
        return ($this->check_in && $this->check_out) ? Carbon::parse($this->check_out)->diffInDays(Carbon::parse($this->check_in)) : 0;
    }

    public function getRoomNameAttribute(){
        return $this->room ? $this->room->name :  'no related room' ;
    }
//---------------------------------------------------------------------------------------------------
    public function getRoomPricePerNightAttribute(){
        return $this->room ? $this->room->price_per_night : 0 ;
    }

    public function getRoomOfferAttribute(){
        return $this->room ? $this->room->has_offer : 0 ;
    }

    public function getSavingsAttribute(){
        return ($this->room->price_per_night * $this->room->has_offer)/100;
    }

    public function getRoomTotalPriceAttribute(){
        return (($this->room->price_per_night - $this->savings ) * ($this->total_nights * $this->room_count) );
    }
//---------------------------------------------------------------------------------------------------
    public function getAvailableRoomsAttribute(){
        return $this->room ? $this->room->available_rooms : 0;
    }

    public function getOrderUserIdAttribute(){
        return $this->hotelOrder ? $this->hotelOrder->user_id : 0;
    }

    public function getHotelIdAttribute(){
        return $this->room ? $this->room->hotel_id : 0;
    }

    public function getHotelNameAttribute(){
        return $this->room ? $this->room->room_hotel_name : 0;
    }

    public function getHotelImageAttribute(){
        return $this->room ? $this->room->room_hotel_image : 0;
    }

    public function getRoomImageAttribute(){
        return $this->room ? $this->room->room_images : 0;
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'user not found';
    }
    //----------------------------------- control panel -----------------------------

    public function getOrderItemDisplayDataAttribute(){
        return [
            'id' => $this->id,
            'user_id'=>$this->order_user_id,
            'order_id'=>$this->order_id,
            'room_id'=>$this->room_id,
            'room_name'=>$this->room_name,
            'check_in'=>$this->check_in,
            'check_out'=>$this->check_out,
            'room_count'=>$this->room_count,
            'total_nights'=>$this->total_nights,
            'room_price_per_night'=>$this->room_price_per_night,
            'room_has_offer'=>$this->room_offer,
            'savings_per_room'=>$this->savings,
            'order_total_price'=>$this->room_total_price,
            'created_from'=>$this->created_from,
            'created_at'=>$this->created_at->format('m/d/Y'),
            'tools'=>$this->edit.'&nbsp'.$this->delete
        ];
    }

    /**
     * Scopes
     */
    public function scopeSearch($query,$searchWord)
    {
//        dd($searchWord);
        return $query->where('name', 'like', "%" . $searchWord . "%")
            ->orWhere('address', 'like', "%" . $searchWord . "%")
            ->orWhere('details', 'like', "%" . $searchWord . "%");
    }

    public function getEditAttribute(){
        return '<button type="button" class="btn btn-warning" data-toggle="tooltip" data-placement="top" rel="tooltip" title="Edit '.$this->name.'" onclick="update(\''.route('mosques.edit',$this->id).'\',this)"><i class="mdi mdi-grease-pencil"></i></button>';
    }
    public function getDeleteAttribute(){
        return '<button type="button" class="btn btn-danger" data-toggle="tooltip" data-placement="top" rel="tooltip" title="Delete '.$this->name.'" onclick="deleteItem(\''.route('mosques.destroy',$this->id).'\')"><i class="mdi mdi-delete-forever"></i></button>';
    }


    //---------------------------------------------------------------------------------------------------------

}
