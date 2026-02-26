// Direct URLs and asset paths
const assetUrl = (key, fallback = "/assets/FiestaResort1.jpg") => {
  const assets = {
    resort1: "/assets/FiestaResort1.jpg",
    resort2: "/assets/FiestaResort2.jpg",
    resort3: "/assets/FiestaResort3.jpg",
    resort4: "/assets/FiestaResort4.jpg",
    resort5: "/assets/FiestaResort5.jpg",
  };
  return assets[key] || fallback;
};

// API base URL
const apiBaseUrl = "/client/bookings";

// Get CSRF token from meta tag
function getCsrfToken() {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  return metaTag ? metaTag.getAttribute("content") : "";
}

document.addEventListener("DOMContentLoaded", function () {
  checkAuthentication();
  loadBookings();
  setupFilters();
  setupModal();
});

function checkAuthentication() {
  const isLoggedIn = window.laravelAuth?.isAuthenticated || localStorage.getItem("isLoggedIn") === "true";
  const userRole = window.laravelAuth?.user?.role || localStorage.getItem("userRole");

  // Prevent admins from accessing client pages
  if (isLoggedIn && userRole === 'admin') {
    if (window.showError) window.showError("Admins cannot access client pages. Redirecting to admin dashboard...");
    setTimeout(() => {
      window.location.href = "/admin/dashboard";
    }, 1000);
    return;
  }

  if (!isLoggedIn) {
    if (window.showError) {
      window.showError("Please login to view your bookings");
    }
    const loginLink = document.createElement("a");
    loginLink.href = "/login";
    document.body.appendChild(loginLink);
    loginLink.click();
    document.body.removeChild(loginLink);
  }
}

// Load bookings from API
async function loadBookings(filter = "all") {
  const bookingsList = document.getElementById("bookingsList");
  const emptyState = document.getElementById("emptyState");

  if (!bookingsList || !emptyState) return;

  try {
    const url = apiBaseUrl || "/client/bookings";
    const response = await fetch(url, {
      method: "GET",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    });

    if (!response.ok) {
      throw new Error("Failed to load bookings");
    }

    const result = await response.json();

    if (result.success && result.data) {
      let bookings = result.data;

      // Filter bookings
      if (filter !== "all") {
        const filterMap = {
          'upcoming': ['pending', 'confirmed'],
          'completed': ['checked-in'],
          'cancelled': ['cancelled'],
        };
        
        const statuses = filterMap[filter] || [filter];
        bookings = bookings.filter(booking => 
          statuses.includes(booking.statusKey?.toLowerCase() || booking.status?.toLowerCase())
        );
      }

      bookingsList.innerHTML = "";

      if (bookings.length === 0) {
        emptyState.style.display = "block";
        bookingsList.style.display = "none";
      } else {
        emptyState.style.display = "none";
        bookingsList.style.display = "grid";

        bookings.forEach((booking) => {
          const bookingCard = createBookingCard(booking);
          bookingsList.appendChild(bookingCard);
        });
        
        attachBookingEventListeners();
      }
    } else {
      // Fallback to localStorage
      loadBookingsFromLocalStorage(filter);
    }
  } catch (error) {
    console.error("Error loading bookings:", error);
    // Fallback to localStorage
    loadBookingsFromLocalStorage(filter);
  }
}

// Fallback to localStorage
function loadBookingsFromLocalStorage(filter = "all") {
  const bookingsList = document.getElementById("bookingsList");
  const emptyState = document.getElementById("emptyState");
  const userEmail = window.laravelAuth?.user?.email || localStorage.getItem("userEmail");

  if (!bookingsList || !emptyState) return;

  let bookings = JSON.parse(localStorage.getItem("myBookings") || "[]");

  if (bookings.length === 0) {
    bookings = generateSampleBookings(userEmail);
    localStorage.setItem("myBookings", JSON.stringify(bookings));
  }

  let filteredBookings = bookings;
  if (filter !== "all") {
    filteredBookings = bookings.filter(
      (booking) => booking.status?.toLowerCase() === filter
    );
  }

  bookingsList.innerHTML = "";

  if (filteredBookings.length === 0) {
    emptyState.style.display = "block";
    bookingsList.style.display = "none";
  } else {
    emptyState.style.display = "none";
    bookingsList.style.display = "grid";

    filteredBookings.forEach((booking) => {
      const bookingCard = createBookingCard(booking);
      bookingsList.appendChild(bookingCard);
    });
    
    attachBookingEventListeners();
  }
}

function createBookingCard(booking) {
  const card = document.createElement("div");
  card.className = "booking-card";
  card.setAttribute("data-booking-id", booking.id);

  const statusKey = booking.statusKey || booking.status?.toLowerCase() || 'pending';
  const statusClass = statusKey.replace(/\s+/g, "-");
  const checkInDate = new Date(booking.checkIn || booking.check_in);
  const checkOutDate = new Date(booking.checkOut || booking.check_out);
  const nights = booking.nights || Math.ceil(
    (checkOutDate - checkInDate) / (1000 * 60 * 60 * 24)
  );
  const displayStatus = booking.status || 'Pending';

  card.innerHTML = `
    <div class="booking-card-content">
      <div class="booking-image">
        <img src="${booking.image || assetUrl('resort1')}" alt="${booking.hotel || 'Hotel'}" />
      </div>

      <div class="booking-info">
        <div class="booking-header">
          <div>
            <h3 class="booking-title">${booking.hotel || booking.roomType || 'Hotel'}</h3>
            <div class="booking-location">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              ${booking.location || 'Brgy. Ipil, Surigao City'}
            </div>
          </div>
          <span class="booking-status ${statusClass}">${displayStatus}</span>
        </div>

        <div class="booking-details-grid">
          <div class="booking-detail">
            <span class="booking-detail-label">Check-in</span>
            <span class="booking-detail-value">${formatDate(booking.checkIn || booking.check_in)}</span>
          </div>
          <div class="booking-detail">
            <span class="booking-detail-label">Check-out</span>
            <span class="booking-detail-value">${formatDate(booking.checkOut || booking.check_out)}</span>
          </div>
          <div class="booking-detail">
            <span class="booking-detail-label">Guests</span>
            <span class="booking-detail-value">${booking.guests || 2} ${booking.guests > 1 ? "Guests" : "Guest"}</span>
          </div>
          <div class="booking-detail">
            <span class="booking-detail-label">Nights</span>
            <span class="booking-detail-value">${nights} ${nights > 1 ? "Nights" : "Night"}</span>
          </div>
        </div>

        <div class="booking-id">Booking ID: ${booking.id}</div>
      </div>

      <div class="booking-actions">
        <button class="btn btn-primary view-details-btn" data-booking-id="${booking.id}" type="button">View Details</button>
        ${
          (statusKey === "pending" || statusKey === "confirmed" || displayStatus === "Upcoming" || displayStatus === "Confirmed")
            ? `<button class="btn btn-secondary modify-booking-btn" data-booking-id="${booking.id}" type="button">
                Modify
              </button>
              <button class="btn btn-danger cancel-booking-btn" data-booking-id="${booking.id}" type="button">
                Cancel
              </button>`
            : ""
        }
      </div>
    </div>
  `;

  return card;
}

function attachBookingEventListeners() {
  const viewDetailsButtons = document.querySelectorAll(".view-details-btn");
  viewDetailsButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const bookingId = btn.getAttribute("data-booking-id");
      viewBookingDetails(bookingId);
    });
  });

  const modifyButtons = document.querySelectorAll(".modify-booking-btn");
  modifyButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const bookingId = btn.getAttribute("data-booking-id");
      modifyBooking(bookingId);
    });
  });

  const cancelButtons = document.querySelectorAll(".cancel-booking-btn");
  cancelButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const bookingId = btn.getAttribute("data-booking-id");
      cancelBooking(bookingId);
    });
  });
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return "N/A";
  const options = { month: "short", day: "numeric", year: "numeric" };
  return date.toLocaleDateString("en-US", options);
}

function setupFilters() {
  const filterButtons = document.querySelectorAll(".filter-btn");

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      button.classList.add("active");
      const filter = button.getAttribute("data-filter");
      loadBookings(filter);
    });
  });
}

async function viewBookingDetails(bookingId) {
  try {
    const url = apiBaseUrl || "/client/bookings";
    const response = await fetch(url, {
      method: "GET",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    });

    let booking = null;

    if (response.ok) {
      const result = await response.json();
      if (result.success && result.data) {
        booking = result.data.find(b => b.id == bookingId);
      }
    }

    // Fallback to localStorage if API fails or booking not found
    if (!booking) {
      const bookings = JSON.parse(localStorage.getItem("myBookings") || "[]");
      booking = bookings.find((b) => b.id == bookingId);
    }

    if (!booking) {
      if (window.showError) window.showError("Booking not found");
      return;
    }

    showBookingModal(booking);
  } catch (error) {
    console.error("Error loading booking details:", error);
    // Fallback to localStorage
    const bookings = JSON.parse(localStorage.getItem("myBookings") || "[]");
    const booking = bookings.find((b) => b.id == bookingId);
    if (booking) {
      showBookingModal(booking);
    } else {
      if (window.showError) window.showError("Failed to load booking details");
    }
  }
}

function showBookingModal(booking) {
  const modal = document.getElementById("bookingModal");
  const modalBody = document.getElementById("modalBody");

  if (!modal || !modalBody) return;

  const checkInDate = new Date(booking.checkIn || booking.check_in);
  const checkOutDate = new Date(booking.checkOut || booking.check_out);
  const nights = booking.nights || Math.ceil(
    (checkOutDate - checkInDate) / (1000 * 60 * 60 * 24)
  );
  const statusKey = booking.statusKey || booking.status?.toLowerCase() || 'pending';
  const statusClass = statusKey.replace(/\s+/g, "-");
  const displayStatus = booking.status || 'Pending';
  
  // Normalize booking data
  const normalizedBooking = {
    hotel: booking.hotel || booking.roomType || "Unknown Hotel",
    location: booking.location || "Brgy. Ipil, Surigao City",
    image: booking.image || assetUrl("resort1"),
    roomType: booking.roomType || booking.room?.title || "Standard Room",
    guestName: booking.guestName || booking.guest_name || window.laravelAuth?.user?.name || "Guest",
    guestEmail: booking.guestEmail || booking.guest_email || window.laravelAuth?.user?.email || "guest@example.com",
    guestPhone: booking.guestPhone || booking.guest_phone || "",
    totalPrice: parseFloat(booking.totalPrice || booking.total_price || 0),
    checkIn: booking.checkIn || booking.check_in,
    checkOut: booking.checkOut || booking.check_out,
    guests: booking.guests || 2,
    status: displayStatus,
    statusKey: statusKey,
    id: booking.id,
    bookingDate: booking.bookingDate || booking.created_at || booking.checkIn || booking.check_in,
    roomNumber: booking.roomNumber || booking.room_number || null,
  };

  modalBody.innerHTML = `
    <div class="modal-image">
      <img src="${normalizedBooking.image}" alt="${normalizedBooking.hotel}" />
    </div>

    <div class="modal-section">
      <h3 class="booking-title">${normalizedBooking.hotel}</h3>
      <div class="booking-location">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
          <circle cx="12" cy="10" r="3"></circle>
        </svg>
        ${normalizedBooking.location}
      </div>
      ${normalizedBooking.roomNumber ? `<div style="margin-top: 8px; color: #64748b;">Room: ${normalizedBooking.roomNumber}</div>` : ''}
      <span class="booking-status ${statusClass}" style="margin-top: 12px; display: inline-block;">${normalizedBooking.status}</span>
    </div>

    <div class="modal-section">
      <h4 class="modal-section-title">Booking Information</h4>
      <div class="modal-details-grid">
        <div class="modal-detail">
          <span class="modal-detail-label">Booking ID</span>
          <span class="modal-detail-value">${normalizedBooking.id}</span>
        </div>
        <div class="modal-detail">
          <span class="modal-detail-label">Booking Date</span>
          <span class="modal-detail-value">${formatDate(normalizedBooking.bookingDate)}</span>
        </div>
        <div class="modal-detail">
          <span class="modal-detail-label">Check-in</span>
          <span class="modal-detail-value">${formatDate(normalizedBooking.checkIn)}</span>
        </div>
        <div class="modal-detail">
          <span class="modal-detail-label">Check-out</span>
          <span class="modal-detail-value">${formatDate(normalizedBooking.checkOut)}</span>
        </div>
        <div class="modal-detail">
          <span class="modal-detail-label">Number of Nights</span>
          <span class="modal-detail-value">${nights} ${nights > 1 ? "Nights" : "Night"}</span>
        </div>
        <div class="modal-detail">
          <span class="modal-detail-label">Guests</span>
          <span class="modal-detail-value">${normalizedBooking.guests} ${normalizedBooking.guests > 1 ? "Guests" : "Guest"}</span>
        </div>
      </div>
    </div>

    <div class="modal-section">
      <h4 class="modal-section-title">Guest Information</h4>
      <div class="modal-details-grid">
        <div class="modal-detail">
          <span class="modal-detail-label">Full Name</span>
          <span class="modal-detail-value">${normalizedBooking.guestName}</span>
        </div>
        <div class="modal-detail">
          <span class="modal-detail-label">Email</span>
          <span class="modal-detail-value">${normalizedBooking.guestEmail}</span>
        </div>
        ${normalizedBooking.guestPhone ? `
        <div class="modal-detail">
          <span class="modal-detail-label">Phone</span>
          <span class="modal-detail-value">${normalizedBooking.guestPhone}</span>
        </div>
        ` : ''}
        <div class="modal-detail">
          <span class="modal-detail-label">Room Type</span>
          <span class="modal-detail-value">${normalizedBooking.roomType}</span>
        </div>
      </div>
    </div>

    <div class="modal-price">
      <span class="modal-price-label">Total Amount</span>
      <span class="modal-price-value">₱${normalizedBooking.totalPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
    </div>

    ${
      (normalizedBooking.statusKey === "pending" || 
       normalizedBooking.statusKey === "confirmed" || 
       normalizedBooking.status === "Upcoming" || 
       normalizedBooking.status === "Confirmed")
        ? `<div class="modal-actions">
          <button class="btn btn-secondary modal-modify-btn" data-booking-id="${normalizedBooking.id}" type="button">
            Modify Booking
          </button>
          <button class="btn btn-danger modal-cancel-btn" data-booking-id="${normalizedBooking.id}" type="button">
            Cancel Booking
          </button>
        </div>`
        : ""
    }
  `;

  modal.classList.add("show");
  
  // Attach event listeners to modal buttons
  const modalModifyBtn = modal.querySelector(".modal-modify-btn");
  const modalCancelBtn = modal.querySelector(".modal-cancel-btn");
  
  if (modalModifyBtn) {
    modalModifyBtn.addEventListener("click", () => {
      closeModal();
      modifyBooking(normalizedBooking.id);
    });
  }
  
  if (modalCancelBtn) {
    modalCancelBtn.addEventListener("click", () => {
      cancelBooking(normalizedBooking.id);
    });
  }
}

function setupModal() {
  const modal = document.getElementById("bookingModal");
  const modalClose = document.getElementById("modalClose");

  if (modalClose) {
    modalClose.addEventListener("click", closeModal);
  }

  if (modal) {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        closeModal();
      }
    });
  }

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      const modal = document.getElementById("bookingModal");
      if (modal && modal.classList.contains("show")) {
        closeModal();
      }
    }
  });
}

function closeModal() {
  const modal = document.getElementById("bookingModal");
  if (modal) {
    modal.classList.remove("show");
  }
}

let currentModifyBooking = null;

async function modifyBooking(bookingId) {
  try {
    // Fetch booking details
    const url = apiBaseUrl || "/client/bookings";
    const response = await fetch(url, {
      method: "GET",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    });

    let booking = null;

    if (response.ok) {
      const result = await response.json();
      if (result.success && result.data) {
        booking = result.data.find(b => b.id == bookingId);
      }
    }

    // Fallback to localStorage if API fails
    if (!booking) {
      const bookings = JSON.parse(localStorage.getItem("myBookings") || "[]");
      booking = bookings.find((b) => b.id == bookingId);
    }

    if (!booking) {
      if (window.showError) window.showError("Booking not found");
      return;
    }

    currentModifyBooking = booking;
    showModifyModal(booking);
  } catch (error) {
    console.error("Error loading booking:", error);
    if (window.showError) window.showError("Failed to load booking details");
  }
}

function showModifyModal(booking) {
  const modal = document.getElementById("modifyBookingModal");
  const checkInInput = document.getElementById("modifyCheckInDate");
  const checkOutInput = document.getElementById("modifyCheckOutDate");
  const nightsCount = document.getElementById("modifyNightsCount");
  const totalPrice = document.getElementById("modifyTotalPrice");

  if (!modal || !checkInInput || !checkOutInput) return;

  // Set current values
  const checkIn = booking.checkIn || booking.check_in;
  const checkOut = booking.checkOut || booking.check_out;
  
  checkInInput.value = checkIn;
  checkOutInput.value = checkOut;

  // Set minimum dates
  const today = new Date().toISOString().split('T')[0];
  checkInInput.min = today;
  checkOutInput.min = today;

  // Calculate initial nights and price
  updateModifyPriceCalculation(booking);

  // Setup date change handlers
  checkInInput.onchange = function() {
    const newCheckIn = new Date(checkInInput.value + 'T00:00:00');
    const nextDay = new Date(newCheckIn);
    nextDay.setDate(nextDay.getDate() + 1);
    checkOutInput.min = nextDay.toISOString().split('T')[0];
    
    // Auto-update check-out if it's before new minimum
    if (checkOutInput.value) {
      const currentCheckOut = new Date(checkOutInput.value + 'T00:00:00');
      if (currentCheckOut <= newCheckIn) {
        checkOutInput.value = nextDay.toISOString().split('T')[0];
      }
    }
    
    updateModifyPriceCalculation(booking);
  };

  checkOutInput.onchange = function() {
    updateModifyPriceCalculation(booking);
  };

  modal.classList.add("show");
}

function updateModifyPriceCalculation(booking) {
  const checkInInput = document.getElementById("modifyCheckInDate");
  const checkOutInput = document.getElementById("modifyCheckOutDate");
  const nightsCount = document.getElementById("modifyNightsCount");
  const totalPrice = document.getElementById("modifyTotalPrice");

  if (!checkInInput.value || !checkOutInput.value) return;

  const checkIn = new Date(checkInInput.value + 'T00:00:00');
  const checkOut = new Date(checkOutInput.value + 'T00:00:00');
  const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));

  if (nights > 0) {
    const pricePerNight = (booking.totalPrice || 0) / (booking.nights || 1);
    const newTotal = Math.round(pricePerNight * nights);
    
    nightsCount.textContent = nights;
    totalPrice.textContent = newTotal.toLocaleString();
  }
}

function hideModifyModal() {
  const modal = document.getElementById("modifyBookingModal");
  if (modal) {
    modal.classList.remove("show");
  }
  currentModifyBooking = null;
}

// Setup modify modal handlers
document.addEventListener("DOMContentLoaded", function() {
  const modifyModalClose = document.getElementById("modifyModalClose");
  const modifyModalCancel = document.getElementById("modifyModalCancel");
  const modifyModalConfirm = document.getElementById("modifyModalConfirm");

  if (modifyModalClose) {
    modifyModalClose.addEventListener("click", hideModifyModal);
  }

  if (modifyModalCancel) {
    modifyModalCancel.addEventListener("click", hideModifyModal);
  }

  if (modifyModalConfirm) {
    modifyModalConfirm.addEventListener("click", submitModifyBooking);
  }

  // Close on outside click
  const modal = document.getElementById("modifyBookingModal");
  if (modal) {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        hideModifyModal();
      }
    });
  }
});

async function submitModifyBooking() {
  if (!currentModifyBooking) return;

  const checkInInput = document.getElementById("modifyCheckInDate");
  const checkOutInput = document.getElementById("modifyCheckOutDate");
  const confirmBtn = document.getElementById("modifyModalConfirm");

  if (!checkInInput.value || !checkOutInput.value) {
    if (window.showError) window.showError("Please select both check-in and check-out dates");
    return;
  }

  const checkIn = new Date(checkInInput.value + 'T00:00:00');
  const checkOut = new Date(checkOutInput.value + 'T00:00:00');

  if (checkOut <= checkIn) {
    if (window.showError) window.showError("Check-out date must be after check-in date");
    return;
  }

  confirmBtn.disabled = true;
  confirmBtn.textContent = "Updating...";

  try {
    const response = await fetch(`${apiBaseUrl}/${currentModifyBooking.id}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": getCsrfToken(),
      },
      credentials: "same-origin",
      body: JSON.stringify({
        check_in: checkInInput.value,
        check_out: checkOutInput.value,
      }),
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || "Failed to update booking");
    }

    if (window.showSuccess) {
      window.showSuccess(result.message || "Booking dates updated successfully!");
    }
    
    hideModifyModal();
    confirmBtn.disabled = false;
    confirmBtn.textContent = "Update Booking";
    
    // Reload bookings to show updated data
    setTimeout(() => {
      loadBookings();
    }, 1000);
  } catch (error) {
    console.error("Error modifying booking:", error);
    if (window.showError) {
      window.showError(error.message || "Failed to modify booking. Please try again or contact the resort.");
    }
    confirmBtn.disabled = false;
    confirmBtn.textContent = "Update Booking";
  }
}

let pendingCancelBookingId = null;
const cancelBookingModal = document.getElementById("cancelBookingModal");
const cancelBookingModalConfirmBtn = document.getElementById("cancelBookingModalConfirmBtn");
const cancelBookingModalCancelBtn = document.getElementById("cancelBookingModalCancelBtn");

function showCancelBookingModal(bookingId) {
  pendingCancelBookingId = bookingId;
  if (cancelBookingModal) {
    cancelBookingModal.classList.add("show");
  }
}

function hideCancelBookingModal() {
  if (cancelBookingModal) {
    cancelBookingModal.classList.remove("show");
  }
  pendingCancelBookingId = null;
}

if (cancelBookingModalConfirmBtn) {
  cancelBookingModalConfirmBtn.addEventListener("click", async () => {
    if (pendingCancelBookingId) {
      try {
        const url = (apiBaseUrl || "/client/bookings") + `/${pendingCancelBookingId}/cancel`;
        const response = await fetch(url, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": getCsrfToken(),
          },
          credentials: "same-origin",
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
          throw new Error(result.message || "Failed to cancel booking");
        }

        closeModal();
        hideCancelBookingModal();

        const activeFilter = document.querySelector(".filter-btn.active");
        const currentFilter = activeFilter
          ? activeFilter.getAttribute("data-filter")
          : "all";
        loadBookings(currentFilter);

        if (window.showSuccess) window.showSuccess(result.message || "Booking cancelled successfully");
      } catch (error) {
        console.error("Error cancelling booking:", error);
        if (window.showError) window.showError(error.message || "Failed to cancel booking. Please try again.");
      }
    }
  });
}

if (cancelBookingModalCancelBtn) {
  cancelBookingModalCancelBtn.addEventListener("click", hideCancelBookingModal);
}

if (cancelBookingModal) {
  cancelBookingModal.addEventListener("click", (event) => {
    if (event.target === cancelBookingModal) {
      hideCancelBookingModal();
    }
  });
}

function cancelBooking(bookingId) {
  showCancelBookingModal(bookingId);
}

function generateSampleBookings(userEmail) {
  const today = new Date();
  const userName = userEmail ? userEmail.split("@")[0] : "Guest";
  const capitalizedName =
    userName.charAt(0).toUpperCase() + userName.slice(1).replace(/[._-]/g, " ");

  return [
    {
      id: "BR" + Math.random().toString(36).substr(2, 9).toUpperCase(),
      hotel: "Fiesta Resort Main",
      location: "Brgy. Ipil, Surigao City",
      image: assetUrl("resort1"),
      roomType: "Standard Room",
      checkIn: new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split("T")[0],
      checkOut: new Date(today.getTime() + 10 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split("T")[0],
      guests: 2,
      guestName: capitalizedName,
      guestEmail: userEmail || "guest@example.com",
      guestPhone: "+63 912 345 6789",
      totalPrice: 4500.0,
      status: "Upcoming",
      bookingDate: new Date(today.getTime() - 3 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split("T")[0],
    },
  ];
}

window.viewBookingDetails = viewBookingDetails;
window.modifyBooking = modifyBooking;
window.cancelBooking = cancelBooking;
window.closeModal = closeModal;
