<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Guest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Store a new booking/reservation.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'room_type' => 'required|string|max:255',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guest_phone' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
            'payment_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Auto-assign an available room
        // First, get all rooms of the requested type that are marked as available
        $rooms = Room::where('room_type', $validated['room_type'])
            ->where('status', 'available')
            ->get();

        if ($rooms->isEmpty()) {
            // Check if the room type exists at all
            $roomTypeExists = Room::where('room_type', $validated['room_type'])->exists();
            if (!$roomTypeExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid room type selected. Please refresh the page and try again.',
                ], 422);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No rooms of this type are currently available. Please contact the resort for assistance.',
            ], 422);
        }

        // Find a room that is available for the requested dates
        $availableRoom = $rooms->first(function ($room) use ($validated) {
                return $room->isAvailableForDates($validated['check_in'], $validated['check_out']);
            });

        if (!$availableRoom) {
            // Provide more helpful error message
            $totalRoomsOfType = $rooms->count();
            return response()->json([
                'success' => false,
                'message' => "No available rooms of type '{$validated['room_type']}' for the selected dates ({$validated['check_in']} to {$validated['check_out']}). All {$totalRoomsOfType} room(s) of this type are already booked. Please try different dates or contact the resort.",
            ], 422);
        }

        // Calculate total price
        $nights = Carbon::parse($validated['check_in'])->diffInDays(Carbon::parse($validated['check_out']));
        $totalPrice = $availableRoom->price_per_night * $nights;

        // Create or update guest
        $guest = Guest::firstOrNew(['email' => $user->email]);
        $guest->name = $user->name;
        if ($validated['guest_phone']) {
            $guest->phone = $validated['guest_phone'];
        }
        if (!$guest->start_since || Carbon::parse($validated['check_in'])->lt($guest->start_since)) {
            $guest->start_since = Carbon::parse($validated['check_in']);
        }
        $guest->save();

        // Build notes with payment info (optional fields)
        $notes = [];
        if (!empty($validated['payment_method'])) {
            $notes[] = "Preferred Payment Method: {$validated['payment_method']}";
        }
        if (!empty($validated['payment_number'])) {
            $notes[] = "Contact Number: {$validated['payment_number']}";
        }
        if (!empty($request->input('notes'))) {
            $notes[] = "Special Requests: " . $request->input('notes');
        }
        if (empty($notes)) {
            $notes[] = "Payment will be collected upon check-in at the resort.";
        }
        $notesString = implode("\n", $notes);

        // Create reservation
        $reservation = Reservation::create([
            'guest_name' => $user->name,
            'guest_email' => $user->email,
            'guest_phone' => $validated['guest_phone'] ?? $user->phone,
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'room_type' => $validated['room_type'],
            'room_id' => $availableRoom->id,
            'status' => 'pending', // Start as pending, admin can confirm
            'total_price' => $totalPrice,
            'notes' => $notesString ?: null,
        ]);

        // Update guest total stays
        $guest->total_stays = Reservation::where('guest_email', $user->email)
            ->where('status', '!=', 'cancelled')
            ->count();
        $guest->save();

        // Create notification for admin
        Notification::createNotification(
            'reservation',
            'New Booking',
            "New booking from {$user->name} - {$validated['room_type']}",
            route('admin.reservations')
        );

        return response()->json([
            'success' => true,
            'message' => 'Reservation submitted successfully! Your booking is pending confirmation. The resort will confirm your reservation when you arrive. You will receive a notification once it\'s confirmed.',
            'data' => [
                'reservation_id' => $reservation->id,
                'room_number' => $availableRoom->room_number,
                'total_price' => $totalPrice,
                'check_in' => $reservation->check_in->format('Y-m-d'),
                'check_out' => $reservation->check_out->format('Y-m-d'),
                'status' => 'pending',
            ],
        ], 201);
    }

    /**
     * Get available rooms for booking.
     */
    public function getAvailableRooms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_type' => 'required|string',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $rooms = Room::where('room_type', $validated['room_type'])
            ->where('status', 'available')
            ->get()
            ->filter(function ($room) use ($validated) {
                return $room->isAvailableForDates($validated['check_in'], $validated['check_out']);
            })
            ->values()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'room_type' => $room->room_type,
                    'price_per_night' => $room->price_per_night,
                    'max_occupancy' => $room->max_occupancy,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Get user's bookings/reservations.
     */
    public function getBookings(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservations = Reservation::where('guest_email', $user->email)
            ->with('room')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reservation) {
                $checkIn = Carbon::parse($reservation->check_in);
                $checkOut = Carbon::parse($reservation->check_out);
                $nights = $checkIn->diffInDays($checkOut);
                
                // Map room type to hotel name
                $hotelMapping = [
                    'Standard Room' => 'Fiesta Resort Main',
                    'Deluxe King Suite' => 'Ocean View Villa',
                    'Executive Suite' => 'Mountain Peak Resort',
                    'Presidential Suite' => 'Garden Paradise',
                ];
                
                $hotelName = $hotelMapping[$reservation->room_type] ?? $reservation->room_type;
                
                // Map room type to image
                $imageMapping = [
                    'Standard Room' => 'FiestaResort1.jpg',
                    'Deluxe King Suite' => 'FiestaResort2.jpg',
                    'Executive Suite' => 'FiestaResort3.jpg',
                    'Presidential Suite' => 'FiestaResort4.jpg',
                ];
                
                $image = $imageMapping[$reservation->room_type] ?? 'FiestaResort1.jpg';
                
                // Map status
                $statusMapping = [
                    'pending' => 'Upcoming',
                    'confirmed' => 'Confirmed',
                    'checked-in' => 'Completed',
                    'cancelled' => 'Cancelled',
                ];
                
                $displayStatus = $statusMapping[$reservation->status] ?? ucfirst($reservation->status);
                
                return [
                    'id' => $reservation->id,
                    'hotel' => $hotelName,
                    'location' => 'Brgy. Ipil, Surigao City',
                    'image' => '/assets/' . $image,
                    'roomType' => $reservation->room_type,
                    'checkIn' => $reservation->check_in->format('Y-m-d'),
                    'checkOut' => $reservation->check_out->format('Y-m-d'),
                    'guests' => $reservation->room ? $reservation->room->max_occupancy : 2,
                    'nights' => $nights,
                    'guestName' => $reservation->guest_name,
                    'guestEmail' => $reservation->guest_email,
                    'guestPhone' => $reservation->guest_phone ?? '',
                    'totalPrice' => (float) $reservation->total_price,
                    'status' => $displayStatus,
                    'statusKey' => $reservation->status,
                    'bookingDate' => $reservation->created_at->format('Y-m-d'),
                    'roomNumber' => $reservation->room ? $reservation->room->room_number : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    /**
     * Update a booking/reservation dates.
     */
    public function updateBooking(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $reservation = Reservation::where('id', $id)
            ->where('guest_email', $user->email)
            ->with('room')
            ->firstOrFail();

        // Only allow modification of pending or confirmed reservations
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or confirmed bookings can be modified.',
            ], 422);
        }

        // Check if the room is available for the new dates
        if ($reservation->room && !$reservation->room->isAvailableForDates(
            $validated['check_in'],
            $validated['check_out'],
            $reservation->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'The room is not available for the selected dates. Please choose different dates.',
            ], 422);
        }

        // Calculate new total price
        $nights = Carbon::parse($validated['check_in'])->diffInDays(Carbon::parse($validated['check_out']));
        $pricePerNight = $reservation->room ? $reservation->room->price_per_night : ($reservation->total_price / Carbon::parse($reservation->check_in)->diffInDays(Carbon::parse($reservation->check_out)));
        $newTotalPrice = $pricePerNight * $nights;

        // Update reservation
        $reservation->check_in = $validated['check_in'];
        $reservation->check_out = $validated['check_out'];
        $reservation->total_price = $newTotalPrice;
        $reservation->save();

        // Create notification for admin
        Notification::createNotification(
            'reservation',
            'Booking Modified',
            "Booking #{$reservation->id} modified by {$user->name}",
            route('admin.reservations')
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking dates updated successfully!',
            'data' => [
                'check_in' => $reservation->check_in->format('Y-m-d'),
                'check_out' => $reservation->check_out->format('Y-m-d'),
                'total_price' => $newTotalPrice,
                'nights' => $nights,
            ],
        ]);
    }

    /**
     * Cancel a booking/reservation.
     */
    public function cancelBooking(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservation = Reservation::where('id', $id)
            ->where('guest_email', $user->email)
            ->firstOrFail();

        // Only allow cancellation of pending or confirmed reservations
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or confirmed bookings can be cancelled.',
            ], 422);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
        ]);
    }
}
