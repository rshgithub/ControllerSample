Tourism App Orders Controller Explanation
This project has developed for an android app which offers RestApi with admin’s control Panel  .
The android app idea in short is a tourism app which is a guide for all tourism places in gaza strip such as mosques , churches and hotels . 
The app also offers direct reservation and payment for hotel rooms .
This Controller example which is named “ orders “ should manage room reservations made by users which works such as “ cart “ in ecommerce projects but with some differences .

Controller methods explanation :
index() :
Returns a collection of all orders made by all users ordered by the last added orders.
getDeletedOrderItems()
Returns a collection of all deleted orders only by all users .
getOrderItemsWithDeleted()
Returns a collection of all orders with deleted ones by all users .
restoreDeletedOrderItem($order)
Restores the deleted order using order id ( id as parameter using model binding ).
store()
Store a new order . ( made only for admin testing purpose  , not used in app )
show($order)
Shows a specific order using order id .
update($order)
Updates a specific order using order id  such as editing the count of reserved rooms .
First it checks if there’s more available rooms of the ordered one , then update the count or gives an apology to the user .

destroy($order)
Destroys a specific order using order id  .
Due to every reservation belong’s to one user order which has a status , one user can only have one order of status “ reservation “ which is waiting for approval from the hotel .
First it will check if this user already has a reservation .
Current_hotel_order is the “reservations “ or “ orders in user cart “ , so if there’s only one item in user’s reservation then the account of reserved rooms of this type will be incremented on the available rooms of it then it will be deleted after that the user’s cart will be destroyed .
If the user has multiple reservations in his cart the same process will go on except the point of destroying the user’s cart , it will remain .
This method allows you to restore the deleted reservation.

forceDeleteOrderItem($order)
Works such as the destroy() method without the chance to restore the deleted reservation.
deleteAuthReservations()
Delete all authenticated user orders after retrieving the count of reserved rooms to the available rooms for each room then delete the user’s cart .
deleteUserReservations($user)
Same process as deleteAuthReservations() but after checking if this user exists using it’s id . ( for admin testing purpose )
addNewOrderItem()
First it will check if this user already has a reservation .
If the user has previous reservations, the new reserved room should be from the same hotel as the previous ones.
If it’s from the same hotel , it will check if this specific room is already reserved for this user or not , if yes it will increase the count of reserved rooms if there’s more available rooms of it and if it was at the same check in & check out date without creating a new reservation in the user's cart . If the check in & check out date is different or the room is not reserved before , it will be added as a new reservation in the user's cart .
If the reserved room is from a different hotel then give an apology to the user with an explanation .
If the reserved rooms count is more than the available rooms or there’s no more available rooms then give an apology to the user with an explanation .

getAuthOrderItems() 
Return authenticated user order with all reservations .
getUserOrderItems
Same process as getAuthOrderItems() but after checking if this user exists using it’s id .( for admin testing purpose )
