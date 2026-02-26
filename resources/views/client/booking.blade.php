@extends('layouts.client')

@section('title', 'Booking - Fiesta Resort')

@push('styles')
  @vite('resources/css/client/booking.css')
@endpush

@push('scripts')
  @vite('resources/js/client/booking.js')
@endpush

@section('content')
  <header class="booking-header">
    <div class="booking-header-container">
      <div class="logo">
        <span class="fiesta-text">Fiesta</span><span class="resort-text">Resort</span>
      </div>
    </div>
  </header>

  <section class="progress-section">
    <div class="progress-container">
      <x-client.progress-step :step="1" :is-active="true" :is-completed="true" />
      <x-client.progress-line />
      <x-client.progress-step :step="2" />
      <x-client.progress-line />
      <x-client.progress-step :step="3" />
    </div>
  </section>

  <main class="booking-main">
    <section class="booking-step" id="step1">
      <div class="step-container">
        <h1 class="step-title">Booking Information</h1>
        <p class="step-description">Please fill up the blank fields below</p>
        <div class="booking-content-grid">
          <div class="hotel-preview">
            <div class="hotel-preview-image">
              <img id="hotelPreviewImage" src="{{ asset('assets/FiestaResort1.jpg') }}" alt="Hotel" />
            </div>
            <div class="hotel-preview-info">
              <h3 class="hotel-preview-name" id="hotelPreviewName">Blue Origin Fams</h3>
              <p class="hotel-preview-location" id="hotelPreviewLocation">Brgy. Ipil, Surigao City</p>
            </div>
          </div>

          <div class="booking-form-section">
            <div class="form-group">
              <label class="form-label">Check-in Date</label>
              <input type="date" class="date-input-field" id="checkInDate" style="width: 100%; padding: 16px 20px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 15px; background: white; cursor: pointer;" />
            </div>

            <div class="form-group">
              <label class="form-label">Check-out Date</label>
              <input type="date" class="date-input-field" id="checkOutDate" style="width: 100%; padding: 16px 20px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 15px; background: white; cursor: pointer;" />
            </div>

            <div class="form-group">
              <label class="form-label">Number of Nights</label>
              <div style="padding: 16px 20px; background-color: #f8f9fa; border-radius: 12px; font-size: 18px; font-weight: 600; color: #152c5b; text-align: center;">
                <span id="daysCount">2</span> Nights
              </div>
            </div>

            <div class="price-summary">
              <p class="price-text">You will pay</p>
              <p class="price-amount">
                <span class="currency">₱</span>
                <span id="totalPrice">1,000</span>
              </p>
              <p class="price-period">
                per <span id="priceDays">2 Days</span>
              </p>
            </div>
          </div>
        </div>

        <div class="step-actions">
          <button class="btn-primary" id="continueToPayment" type="button">Book Now</button>
          <button class="btn-secondary" id="cancelBooking" type="button">Cancel</button>
        </div>
      </div>
    </section>

    <section class="booking-step hidden" id="step2">
      <div class="step-container">
        <h1 class="step-title">Payment Information</h1>
        <p class="step-description">Please provide your payment details. Payment will be processed upon arrival at the resort.</p>
        <div class="payment-content-grid">
          <div class="transfer-summary">
            <h3 class="transfer-title">Booking Summary</h3>
            <div class="transfer-details">
              <p class="transfer-info">
                <span id="paymentDays">2</span> Days at
                <span id="paymentHotel">Fiesta Resort</span>,
              </p>
              <p class="transfer-location" id="paymentLocation">Brgy. Ipil, Surigao City</p>
              <div class="transfer-pricing">
                <p class="transfer-total">
                  Total Amount: <strong id="paymentTotal">2000</strong>
                </p>
                <p class="transfer-note" style="font-size: 14px; color: #64748b; margin-top: 10px;">
                  <em>Note: Payment will be collected upon check-in at the resort. This form is for reservation purposes only.</em>
                </p>
              </div>
            </div>
          </div>

          <div class="payment-form-section">
            <form id="paymentForm">
              <div class="form-group">
                <label class="form-label">Preferred Payment Method (Optional)</label>
                <select class="form-input" id="bankName">
                  <option value="">Select Payment Method (Optional)</option>
                  <option value="Cash">Cash (Pay on Arrival)</option>
                  <option value="GCASH">GCASH</option>
                  <option value="PayMaya">PayMaya</option>
                  <option value="BDO">BDO</option>
                  <option value="BPI">BPI</option>
                  <option value="Metrobank">Metrobank</option>
                </select>
                <small style="display: block; margin-top: 5px; color: #64748b; font-size: 13px;">
                  This is for reference only. Actual payment will be processed at the resort.
                </small>
              </div>
              <div class="form-group">
                <label class="form-label">Contact Number (Optional)</label>
                <input type="text" class="form-input" id="gcashNumber" placeholder="Enter your contact number (e.g., 09XXXXXXXXX)" />
              </div>
              <div class="form-group">
                <label class="form-label">Special Requests or Notes (Optional)</label>
                <textarea class="form-input" id="validationDate" rows="3" placeholder="Any special requests or notes for your stay..."></textarea>
              </div>
            </form>
          </div>
        </div>

        <div class="step-actions">
          <button class="btn-primary" id="completePayment" type="button">Submit Reservation</button>
          <button class="btn-secondary" id="backToBooking" type="button">Cancel</button>
        </div>
      </div>
    </section>

    <section class="booking-step hidden" id="step3">
      <div class="step-container success-container">
        <h1 class="step-title success-title" style="color: #4169e1;">🎉 Reservation Submitted Successfully!</h1>
        <div class="success-illustration">
          <svg viewBox="0 0 200 200" class="success-icon">
            <circle cx="100" cy="100" r="80" fill="#4169E1" />
            <polyline points="65,100 85,120 135,70" fill="none" stroke="white" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="100" cy="100" r="90" fill="none" stroke="#E8EDF5" stroke-width="4" />
            <circle cx="100" cy="100" r="95" fill="none" stroke="#CBD5E1" stroke-width="2" stroke-dasharray="5,5" />
          </svg>
        </div>
        <p class="success-message" style="font-size: 18px; line-height: 1.8; color: #152c5b; max-width: 600px; margin: 2rem auto;">
          <strong style="color: #4169e1; font-size: 20px;">Your booking is pending confirmation.</strong><br />
          <br />
          The resort will confirm your reservation when you arrive. Payment will be collected at check-in.<br />
          <br />
          📧 <strong>Check your email</strong> for booking details<br />
          📱 You'll receive a <strong>notification</strong> once the resort confirms your booking<br />
          <br />
          <span style="color: #64748b; font-size: 16px;">Thank you for choosing Fiesta Resort!</span>
        </p>
        <div class="step-actions">
          <a href="{{ route('client.my-bookings') }}" class="btn-primary" style="text-decoration: none; text-align: center; display: block;">View My Bookings</a>
          <a href="{{ route('client.home') }}" class="btn-link" data-auth-transition>Go to Home</a>
        </div>
      </div>
    </section>
  </main>

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

