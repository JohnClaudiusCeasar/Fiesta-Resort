@extends('layouts.client')

@section('title', 'My Bookings - Fiesta Resort')

@push('styles')
  @vite('resources/css/client/my-bookings.css')
@endpush

@push('scripts')
  @vite('resources/js/client/my-bookings.js')
@endpush

@section('content')
  <x-client.page-header 
    title="My Bookings"
    description="Manage and view all your reservations"
  />

  <section class="bookings-section">
    <div class="bookings-container">
      <div class="bookings-filters">
        <x-client.filter-button label="All Bookings" filter="all" :is-active="true" />
        <x-client.filter-button label="Upcoming" filter="upcoming" />
        <x-client.filter-button label="Completed" filter="completed" />
        <x-client.filter-button label="Cancelled" filter="cancelled" />
      </div>

      <div class="bookings-list" id="bookingsList"></div>

      <x-client.empty-state 
        id="emptyState"
        title="No bookings found"
        description="You haven't made any bookings yet. Start exploring our hotels and rooms!"
        :show-button="true"
        button-text="Explore Rooms"
        :button-url="route('client.rooms')"
      >
        <x-slot:icon>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-icon">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
        </x-slot:icon>
      </x-client.empty-state>
    </div>
  </section>

  <x-client.modal 
    id="bookingModal"
    title="Booking Details"
    close-button-id="modalClose"
  />

  <!-- Modify Booking Modal -->
  <div class="modal" id="modifyBookingModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Modify Booking</h2>
          <button class="modal-close" id="modifyModalClose" type="button">×</button>
        </div>
        <div class="modal-body" id="modifyModalBody">
          <p style="margin-bottom: 1.5rem; color: #64748b;">Update your check-in and check-out dates below.</p>
          
          <div class="form-group">
            <label class="form-label">Check-in Date</label>
            <input type="date" class="date-input-field" id="modifyCheckInDate" style="width: 100%; padding: 16px 20px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 15px; background: white; cursor: pointer;" />
          </div>

          <div class="form-group">
            <label class="form-label">Check-out Date</label>
            <input type="date" class="date-input-field" id="modifyCheckOutDate" style="width: 100%; padding: 16px 20px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 15px; background: white; cursor: pointer;" />
          </div>

          <div class="form-group">
            <label class="form-label">Number of Nights</label>
            <div style="padding: 16px 20px; background-color: #f8f9fa; border-radius: 12px; font-size: 18px; font-weight: 600; color: #152c5b; text-align: center;">
              <span id="modifyNightsCount">0</span> Nights
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">New Total Price</label>
            <div style="padding: 16px 20px; background-color: #f0f9ff; border-radius: 12px; font-size: 24px; font-weight: 700; color: #4169e1; text-align: center;">
              ₱<span id="modifyTotalPrice">0</span>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="display: flex; gap: 1rem; padding: 1.5rem; border-top: 1px solid #e0e0e0;">
          <button class="btn btn-secondary" id="modifyModalCancel" type="button" style="flex: 1;">Cancel</button>
          <button class="btn btn-primary" id="modifyModalConfirm" type="button" style="flex: 1;">Update Booking</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Cancel Booking Confirmation Modal -->
  <x-client.confirmation-modal 
    id="cancelBookingModal"
    title="Cancel Booking"
    message="Are you sure you want to cancel this booking? This action cannot be undone."
    confirm-text="Yes, Cancel"
    cancel-text="Keep Booking"
    confirm-button-class="logout-modal-btn-delete"
  />
@endsection

