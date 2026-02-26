<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Guest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Calculate statistics - only count active reservations (exclude cancelled)
        $totalReservations = Reservation::whereIn('status', ['pending', 'confirmed', 'checked-in'])
            ->count();
        $availableRooms = Room::where('status', 'available')->count();
        
        // Calculate total revenue ONLY from confirmed and checked-in reservations (excludes pending)
        $totalRevenue = Reservation::whereIn('status', ['confirmed', 'checked-in'])
            ->sum('total_price') ?? 0;
        
        // Format revenue
        $formattedRevenue = '₱' . number_format($totalRevenue, 2);
        
        // Get recent activities (last 10 reservations)
        $recentReservations = Reservation::with(['room', 'guest'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $activities = $recentReservations->map(function ($reservation) {
            $activity = '';
            $date = $reservation->created_at->format('Y-m-d');
            $user = $reservation->guest_name;
            
            switch ($reservation->status) {
                case 'confirmed':
                    $activity = "Reservation confirmed for {$reservation->guest_name}";
                    break;
                case 'checked-in':
                    $activity = "Guest checked in - {$reservation->guest_name}";
                    break;
                case 'cancelled':
                    $activity = "Reservation cancelled - {$reservation->guest_name}";
                    break;
                default:
                    $activity = "New reservation created - {$reservation->guest_name}";
                    break;
            }
            
            if ($reservation->room) {
                $activity .= " (Room {$reservation->room->room_number})";
            }
            
            return [
                'activity' => $activity,
                'date' => $date,
                'user' => $user,
            ];
        });
        
        // Add room activities if needed (rooms created recently)
        $recentRooms = Room::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($recentRooms as $room) {
            if ($room->created_at->isAfter(Carbon::now()->subDays(30))) {
                $activities->push([
                    'activity' => "New room added - {$room->room_number} ({$room->room_type})",
                    'date' => $room->created_at->format('Y-m-d'),
                    'user' => 'Admin',
                ]);
            }
        }
        
        // Sort activities by date (most recent first) and limit to 10
        $activities = $activities->sortByDesc(function ($activity) {
            return $activity['date'];
        })->take(10)->values();
        
        return view('admin.dashboard', [
            'totalReservations' => $totalReservations,
            'availableRooms' => $availableRooms,
            'totalRevenue' => $formattedRevenue,
            'activities' => $activities,
        ]);
    }
}
