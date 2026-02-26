<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Fiesta Resort Admin')</title>
    @vite([
      'resources/css/admin/base.css',
      'resources/js/admin/base.js',
      'resources/js/admin/notifications.js',
    ])
    @stack('styles')
    @php
      $userData = auth()->check() ? [
        'email' => auth()->user()->email,
        'name' => auth()->user()->name,
        'role' => auth()->user()->role ?? 'user',
      ] : null;
    @endphp
    <script>
      // Pass Laravel authenticated user data to JavaScript
      window.laravelAuth = {
        isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
        user: @json($userData),
        logoutUrl: "{{ route('logout') }}",
        csrfToken: "{{ csrf_token() }}",
      };
    </script>
  </head>
  <body>
    <header class="admin-header">
      <div class="logo-container">
        <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="#2563eb" />
        </svg>
        <div class="logo-text"><span class="fiesta">Fiesta</span>Resort</div>
      </div>

      <div class="header-icons">
        <div class="dropdown" id="userDropdown">
          <button class="icon-btn" id="userBtn" type="button" aria-label="Account menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </button>
          <div class="dropdown-menu" id="userMenu">
            <div class="dropdown-header">Admin Account</div>
            <a href="{{ route('admin.profile') }}" class="dropdown-item">Profile Settings</a>
            <button class="dropdown-item" type="button">Account Preferences</button>
            <div class="dropdown-divider"></div>
            <button class="dropdown-item" id="logoutBtn" data-trigger-logout type="button">Log Out</button>
          </div>
        </div>
      </div>
    </header>

    <div class="admin-container">
      <aside class="sidebar">
        <nav class="sidebar-menu">
          <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
            </svg>
            <span>Dashboard</span>
          </a>
          <a href="{{ route('admin.reservations') }}" class="menu-item {{ request()->routeIs('admin.reservations') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>Reservations</span>
          </a>
          <a href="{{ route('admin.rooms') }}" class="menu-item {{ request()->routeIs('admin.rooms') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            </svg>
            <span>Rooms & Availability</span>
          </a>
          <a href="{{ route('admin.users') }}" class="menu-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>User Accounts</span>
          </a>
          <a href="{{ route('admin.guests') }}" class="menu-item {{ request()->routeIs('admin.guests') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Guests</span>
          </a>
        </nav>

        <div class="sidebar-bottom">
          <a href="{{ route('admin.settings') }}" class="menu-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 1v6m0 6v6m6-12h-6m-6 0h6m-6 6h6m6 0h-6"></path>
            </svg>
            <span>Settings</span>
          </a>
          <button class="menu-item" data-trigger-logout type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Log Out</span>
          </button>
        </div>
      </aside>

      <main class="main-content">
        @yield('content')
      </main>
    </div>

    <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="hidden">
      @csrf
    </form>

    <!-- Logout Confirmation Modal -->
    <x-admin.confirmation-modal 
      id="logoutModal"
      title="Confirm Logout"
      message="Are you sure you want to log out?"
      confirm-text="Log Out"
      cancel-text="Cancel"
    />

    <!-- Toast Notification Container -->
    <x-admin.toast-notification id="adminToastContainer" />

    @stack('scripts')
    @vite('resources/js/utils/notifications.js')
  </body>
</html>

