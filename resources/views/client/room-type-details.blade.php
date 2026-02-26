@extends('layouts.client')

@section('title', 'Room Type Details - Fiesta Resort')

@push('styles')
  @vite('resources/css/client/room-details.css')
@endpush

@push('scripts')
  @vite('resources/js/client/room-details.js')
@endpush

@section('content')
  <section class="room-details-section" data-rooms-list-url="{{ route('client.rooms.list') }}">
    <div class="room-details-container">
      <x-client.breadcrumb 
        :items="[
          ['label' => 'Home', 'url' => route('client.home')],
          ['label' => 'Rooms', 'url' => route('client.rooms')],
          ['label' => $roomType ?? 'Room Details']
        ]"
      />

      <div class="room-header">
        <h1 class="room-title" id="roomTypeName">{{ $roomType ?? 'All Room Types' }}</h1>
        <p class="room-location" id="resortLocation">Fiesta Resort • Brgy. Ipil, Surigao City, Surigao del Norte</p>
      </div>

      @if($selectedRoomType)
        <div class="room-image-wrapper">
          @php
            $roomImages = [
              'Standard Room' => 'FiestaResort1.jpg',
              'Deluxe King Suite' => 'FiestaResort2.jpg',
              'Executive Suite' => 'FiestaResort3.jpg',
              'Presidential Suite' => 'FiestaResort4.jpg',
            ];
            $image = $roomImages[$selectedRoomType->room_type] ?? 'FiestaResort1.jpg';
          @endphp
          <img src="{{ asset('assets/' . $image) }}" alt="{{ $selectedRoomType->room_type }}" class="room-image" id="roomImage" />
        </div>
      @else
        <div class="room-image-wrapper">
          <img src="{{ asset('assets/FiestaResort1.jpg') }}" alt="Fiesta Resort" class="room-image" id="roomImage" />
        </div>
      @endif

      <section class="available-rooms-section">
        <h2 class="section-heading">Available Room Types</h2>
        <p style="margin-bottom: 2rem; color: #64748b; font-size: 16px;">
          Browse all our room types available at Fiesta Resort. Each room type offers unique features and amenities to make your stay comfortable and memorable.
        </p>
        <div class="rooms-grid-container" id="roomsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;">
          @php
            $roomImages = [
              'Standard Room' => 'FiestaResort1.jpg',
              'Deluxe King Suite' => 'FiestaResort2.jpg',
              'Executive Suite' => 'FiestaResort3.jpg',
              'Presidential Suite' => 'FiestaResort4.jpg',
            ];
            $roomTypeMapping = [
              'Standard Room' => 'standard',
              'Deluxe King Suite' => 'deluxe',
              'Executive Suite' => 'executive',
              'Presidential Suite' => 'presidential',
            ];
          @endphp
          @forelse($roomTypes as $roomType)
            @php
              $type = $roomType['type'];
              $count = $roomCounts[$type] ?? 0;
              $price = $roomType['price_per_night'] ?? $roomType['min_price'] ?? 0;
              $mappedType = $roomTypeMapping[$type] ?? strtolower(str_replace(' ', '-', $type));
            @endphp
            <x-client.room-card 
              title="{{ $type }}"
              location="Brgy. Ipil, Surigao City"
              :price="(int)$price"
              :rating="0"
              image="{{ $roomImages[$type] ?? 'FiestaResort1.jpg' }}"
              badge="{{ $count > 0 && $count <= 2 ? 'Limited' : ($count > 5 ? 'Popular' : null) }}"
              :features="['Free WiFi', 'Modern Amenities']"
              :url="route('client.room-details') . '?room=' . $mappedType . '&room_type=' . urlencode($type)"
              :data-attributes="['room-type' => $mappedType, 'room-name' => $type]"
            >
              {{ $count }} {{ $count == 1 ? 'room' : 'rooms' }} available
            </x-client.room-card>
          @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #64748b;">
              <p>No rooms available at the moment. Please check back later.</p>
            </div>
          @endforelse
        </div>
      </section>
    </div>
  </section>

  @section('footer')
    <footer class="details-footer">
      <div class="become-owner-section">
        <div class="owner-content">
          <div class="owner-left">
            <span class="fiesta-text">Fiesta</span><span class="resort-text">Resort</span>
            <p class="owner-tagline">
              We kaboom your beauty holiday instantly and memorable.
            </p>
          </div>
          @guest
            <div class="owner-right">
              <span class="owner-question">You're not registered yet?</span>
              <a href="{{ route('register') }}" class="register-now-btn" data-auth-transition>Register Now</a>
            </div>
          @endguest
        </div>
      </div>
      <div class="copyright-footer">
        <p>Copyright {{ now()->year }} • All rights reserved • Fiesta Resort</p>
      </div>
    </footer>
  @endsection
@endsection

