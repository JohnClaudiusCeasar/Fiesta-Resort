<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Display the profile page.
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Get booking statistics (exclude cancelled)
        $reservations = Reservation::where('guest_email', $user->email)
            ->whereIn('status', ['pending', 'confirmed', 'checked-in'])
            ->get();
        
        $totalBookings = $reservations->count();
        $completedBookings = $reservations->where('status', 'checked-in')->count();
        $upcomingBookings = $reservations->whereIn('status', ['pending', 'confirmed'])->count();
        // Only count spending from confirmed and checked-in (not pending)
        $totalSpent = $reservations->whereIn('status', ['confirmed', 'checked-in'])->sum('total_price');

        // Get recent bookings
        $recentBookings = $reservations->sortByDesc('created_at')->take(5);

        return view('client.my-profile', [
            'user' => $user,
            'totalBookings' => $totalBookings,
            'completedBookings' => $completedBookings,
            'upcomingBookings' => $upcomingBookings,
            'totalSpent' => $totalSpent,
            'recentBookings' => $recentBookings,
        ]);
    }

    /**
     * Get current user profile via API.
     */
    public function get(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Split name into first and last
        $nameParts = explode(' ', $user->name ?? '', 2);
        
        return response()->json([
            'success' => true,
            'data' => [
                'firstName' => $nameParts[0] ?? '',
                'lastName' => $nameParts[1] ?? '',
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'address' => $user->address ?? '',
                'country_code' => $user->country_code ?? '+63',
            ],
        ]);
    }

    /**
     * Get booking statistics.
     */
    public function getStats(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get active reservations only (exclude cancelled)
        $reservations = Reservation::where('guest_email', $user->email)
            ->whereIn('status', ['pending', 'confirmed', 'checked-in'])
            ->get();
        
        $totalBookings = $reservations->count();
        $completedBookings = $reservations->where('status', 'checked-in')->count();
        $upcomingBookings = $reservations->whereIn('status', ['pending', 'confirmed'])->count();
        // Only count spending from confirmed and checked-in (not pending)
        $totalSpent = $reservations->whereIn('status', ['confirmed', 'checked-in'])->sum('total_price');

        return response()->json([
            'success' => true,
            'data' => [
                'totalBookings' => $totalBookings,
                'completedBookings' => $completedBookings,
                'upcomingBookings' => $upcomingBookings,
                'totalSpent' => number_format($totalSpent, 2),
            ],
        ]);
    }

    /**
     * Get recent bookings.
     */
    public function getRecentBookings(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $bookings = Reservation::where('guest_email', $user->email)
            ->with('room')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'hotel' => $reservation->room_type,
                    'checkIn' => $reservation->check_in->format('Y-m-d'),
                    'checkOut' => $reservation->check_out->format('Y-m-d'),
                    'status' => $reservation->status,
                    'totalPrice' => $reservation->total_price,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Update user profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'dateOfBirth' => 'nullable|date',
        ]);

        // Update user
        $user->name = trim($validated['firstName'] . ' ' . $validated['lastName']);
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        
        // Combine address fields
        $addressParts = array_filter([
            $validated['address'] ?? null,
            $validated['city'] ?? null,
            $validated['country'] ?? null,
        ]);
        $user->address = !empty($addressParts) ? implode(', ', $addressParts) : null;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'firstName' => $validated['firstName'],
                'lastName' => $validated['lastName'],
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
            ],
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
            'confirmPassword' => 'required|string|same:newPassword',
        ]);

        if (!Hash::check($validated['currentPassword'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password = Hash::make($validated['newPassword']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get CSRF token helper.
     */
    private function getCsrfToken(): string
    {
        return csrf_token();
    }
}
