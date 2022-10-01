<?php

namespace App\Http\Controllers\Api;

use App\Events\SendNotification;
use App\Http\Controllers\Controller;

use App\Http\Requests\Api\RoomOrderItems\updateOrderRequest;
use App\Http\Requests\Api\RoomOrderItems\newOrderRequest;
use App\Http\Resources\HotelOrderResource;
use App\Http\Resources\OrderItemResource;
use App\Models\Hotel_Room;
use App\Models\OrderItem;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Laravel\ServiceProvider;


class OrdersController extends Controller

{

    public function index()
    {
        return response()->json( OrderItemResource::collection(OrderItem::orderBy('created_at' , 'DESC')->get()));
    }

    public function getDeletedOrderItems()
    {
        return response()->json( (OrderItem::onlyTrashed()->get() ));
    }

    public function getOrderItemsWithDeleted()
    {
        return response()->json( (OrderItem::withTrashed()->get() ));
    }

    public function restoreDeletedOrderItem($order)
    {
        $trashed = OrderItem::withTrashed()->find($order);
        if($trashed) {
            $trashed->restore();
            return response()->json(['message'=>'success' , 'data' => $trashed]);
        }else{
            return response()->json(['message' => 'this Hotel Order is not trashed']);
        }
    }


    public function create()
    {
        //
    }


    public function store(newOrderRequest $request)
    {
        $order = OrderItem::create($request->validated());
        return response()->json(['message' => 'success', 'data' => OrderItemResource::make($order)]);
    }


    public function show($order)
    {
        if ($order) {
            return response()->json(['message' => 'success', 'data' => OrderItemResource::make($order)]);
        } else {
            return response()->json(['message' => 'this room reservation does not exist']);
        }
    }


    public function edit($order)
    {
    }


    public function update(updateOrderRequest $request, $order)
    {
        if ($order) {

            $room = Hotel_Room::find($order->room_id);
            $room->increment('available_rooms', $order->room_count);

            if ($room->available_rooms >= $request->room_count) {

                $order->update($request->validated());

                $room->decrement('available_rooms', $request->room_count);

                return response()->json(['message' => 'success', 'data' => OrderItemResource::make($order)]);

            } else {
                return response()->json(['message' => 'you cant reserve this number of rooms beacuse it is more than the available of this type ',
                    'available rooms' => $room->available_rooms, 'errors' => 1]);
            }
        } else {
            return response()->json(['message' => 'this room order does not exist']);
        }
    }



    public function destroy($order)
    {
        if ($order) {

            $userCart = Auth::user()->user_order_items_count;
            $room = Hotel_Room::find($order->room_id);

            if($userCart == 1){
                $order->delete();
                $CurrentOrder = Auth::user()->current_hotel_order;
                $room->increment('available_rooms', $order->room_count);
                $CurrentOrder->delete();
            }else{
                $room->increment('available_rooms', $order->room_count);
                $order->delete();
            }
            return response()->json(['message' => 'success']);
        } else {
            return response()->json(['message' => 'this room order does not exist']);
        }
    }

    public function forceDeleteOrderItem($order)
    {
        $order = OrderItem::find($order);
        if ($order) {

            $userCart = Auth::user()->user_order_items_count;
            $room = Hotel_Room::find($order->room_id);

            if($userCart == 1){
                $order->forceDelete();
                $CurrentOrder = Auth::user()->current_hotel_order;
                $room->increment('available_rooms', $order->room_count);
                $CurrentOrder->forceDelete();
            }else{
                $room->increment('available_rooms', $order->room_count);
                $order->forceDelete();
            }
            return response()->json(['message' => 'success']);
        } else {
            return response()->json(['message' => 'this room order does not exist']);
        }
    }

    public function deleteAuthReservations()
    {

        $order = Auth::user()->current_hotel_order;
        if ($order) {

            $allOrderItems = $order->hotel_order_items;
            foreach($allOrderItems as $orderItem){
                $room = Hotel_Room::find($orderItem->room_id);
                $room->increment('available_rooms', $orderItem->room_count);
            }
            OrderItem::where('order_id', $order->id)->delete();
            $order->delete();
            return response()->json(['message' => 'success']);

        } else {
            return response()->json(
                ['message' => 'user does not have any reservations yet ', 'errors' => 1]
            );
        }
    }

    public function deleteUserReservations($user)
    {

        $user = User::find($user);
        if ($user) {
            $order = $user->current_hotel_order;

            if ($order) {

                $allOrderItems = $order->hotel_order_items;
                foreach($allOrderItems as $orderItem){
                    $room = Hotel_Room::find($orderItem->room_id);
                    $room->increment('available_rooms', $orderItem->room_count);
                }
                OrderItem::where('order_id', $order->id)->delete();
                $order->delete();
                return response()->json(['message' => 'success']);

            } else {
                return response()->json(
                    ['message' => 'user does not have any reservations yet ', 'errors' => 1]
                );
            }

        } else {
            return response()->json(['message' => 'this user does not exist']);
        }
    }




    public function addNewOrderItem(newOrderRequest $request)
    {
        $order = Auth::user()->current_hotel_order;
        $room = Hotel_Room::find($request->room_id); // no need to check if room exist

        if ($order) {
            $currentHotelId = $order->current_hotel_id;
            $existedRoom = OrderItem::where('room_id', $request->room_id)->where('order_id', $order->id)->first();

            if ($existedRoom) {

                // if available rooms more than check all data else error
                if (isset($room->available_rooms) && ($room->available_rooms != 0)) {

                    if ($room->available_rooms >= $request->room_count) {

                        if (($existedRoom->room_id == $request->room_id) && ($existedRoom->check_in == $request->check_in)
                            && ($existedRoom->check_out == $request->check_out)) {

                            // increase count in raw and decrease available

                            $existedRoom->increment('room_count', $request->room_count);
                            $room->decrement('available_rooms', $request->room_count);
                            return response()->json(['message' => 'reservation rooms count updated successfully', 'data' => OrderItemResource::make($existedRoom)]);

                        } else {

                            $order_Item = OrderItem::create([
                                'order_id' => $order->id,
                                'room_id' => $request->room_id,
                                'check_in' => $request->check_in,
                                'check_out' => $request->check_out,
                                'room_count' => $request->room_count
                            ]);

                            $room->decrement('available_rooms', $request->room_count);
                            return response()->json(['message' => 'room reserved successfully and added to the your reservations', 'data' => OrderItemResource::make($order_Item)]);
                        }

                    } else {
                        return response()->json(['message' => 'you cant reserve this number of rooms beacuse it is more than the available of this type ',
                            'available rooms' => $room->available_rooms,
                            'errors' => 1]);
                    }
                } else {
                    return response()->json(['message' => 'no more available rooms of this type', 'available rooms' => $room->available_rooms, 'errors' => 1]);
                }

            } else {
                if (isset($room->hotel_id) && ($room->hotel_id == $currentHotelId)) {

                    if (isset($room->available_rooms) && ($room->available_rooms != 0)) {

                        if ($room->available_rooms >= $request->room_count) {
                            $order_Item = OrderItem::create([
                                'order_id' => $order->id,
                                'room_id' => $request->room_id,
                                'check_in' => $request->check_in,
                                'check_out' => $request->check_out,
                                'room_count' => $request->room_count
                            ]);

                            $room->decrement('available_rooms', $request->room_count);

                            return response()->json([
                                'message' => 'room reserved successfully and added to the your reservations',
                                'data' => OrderItemResource::make($order_Item)]);

                        } else {
                            return response()->json(['message' => 'you cant reserve this number of rooms beacuse it is more than the available of this type ',
                                'available rooms' => $room->available_rooms, 'errors' => 1]);
                        }

                    } else {
                        return response()->json(['message' => 'no more available rooms of this type',
                            'available rooms' => $room->available_rooms, 'errors' => 1]);
                    }

                } else {
                    return response()->json(
                        [
                            'message' => 'you cant reserve rooms from multiple hotels ',
                            'current_hotel_id' => Auth::user()->current_order_hotel_id,
                            'current_hotel_name' => Auth::user()->current_order_hotel_name,
                            'requested_hotel_id' => $room->hotel_id,
                            'requested_hotel_name' => $room->room_hotel_name,
                            'errors' => 1
                        ]
                    );
                }
            }

        } else {


            if (isset($room->available_rooms) && ($room->available_rooms != 0)) {

                if ($room->available_rooms >= $request->room_count) {

                    $order = Auth::user()->hotel_orders()->create(['status' => 5]);

                    $order_Item = OrderItem::create([
                        'order_id' => $order->id,
                        'room_id' => $request->room_id,
                        'check_in' => $request->check_in,
                        'check_out' => $request->check_out,
                        'room_count' => $request->room_count
                    ]);

                    $room->decrement('available_rooms', $request->room_count);

                    return response()->json([
                        'message' => 'room reserved successfully and added to the your reservations', 'data' => OrderItemResource::make($order_Item)]);
                } else {
                    return response()->json(['message' => 'you cant reserve this number of rooms beacuse it is more than the available of this type ',
                        'available rooms' => $room->available_rooms, 'errors' => 1]);

                }
            } else {
                return response()->json(['message' => 'no more available rooms of this type',
                    'available rooms' => $room->available_rooms, 'errors' => 1]);
            }
        }

    }


    public function getAuthOrderItems()
    {

        $order = Auth::user()->current_hotel_order;


        if ($order) {
            return response()->json(['message' => 'success', 'data' => HotelOrderResource::make($order) ,
                'hotel_order_items' => OrderItemResource::collection( $order->hotel_order_items)]);
        } else {
            return response()->json(
                ['message' => 'user does not have any reservations yet ']
            );
        }
    }

    public function getUserOrderItems($user)
    {

        $user = User::find($user);
        if ($user) {
            $order = $user->current_hotel_order;
            if ($order) {
                return response()->json(['message' => 'success', 'data' => HotelOrderResource::make($order) ,
                    'hotel_order_items' => OrderItemResource::collection( $order->hotel_order_items)]);
            } else {
                return response()->json(
                    ['message' => 'user does not have any reservations yet ', 'errors' => 1]
                );
            }
        } else {
            return response()->json(['message' => 'this user does not exist']);
        }
    }


}

