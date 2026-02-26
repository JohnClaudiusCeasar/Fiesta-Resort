// Direct URLs and asset paths
const fallbackImage = "/assets/FiestaResort1.jpg";

// API base URL
const apiBaseUrl = "/client/booking";

// Get CSRF token from meta tag
function getCsrfToken() {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  return metaTag ? metaTag.getAttribute("content") : "";
}

document.addEventListener("DOMContentLoaded", function () {
  checkAuthentication();
  loadBookingData();
  initializeDatePicker();
  setupEventListeners();
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
    if (window.showError) window.showError("Please login to continue with booking");
    sessionStorage.setItem("redirectAfterLogin", window.location.href);
    const loginLink = document.createElement("a");
    loginLink.href = "/login";
    loginLink.click();
  }
}

async function loadBookingData() {
  const bookingData = JSON.parse(sessionStorage.getItem("pendingBooking") || "{}");

  if (!bookingData || !bookingData.room) {
    if (window.showError) window.showError("No booking information found. Please go to home page.");
    setTimeout(() => {
      window.location.href = "/";
    }, 2000);
    return;
  }

  // Ensure selected room id is present; if not, block booking early
  if (!bookingData.room.id) {
    if (window.showError) window.showError("Please select a specific room before booking.");
    setTimeout(() => {
      window.location.href = "/client/room-type-details?room_type=" + encodeURIComponent(bookingData.room.roomType || bookingData.room.room_type || "");
    }, 1500);
    return;
  }

  // Update hotel preview
  const hotelNameEl = document.getElementById("hotelPreviewName");
  const hotelLocationEl = document.getElementById("hotelPreviewLocation");
  const hotelImageEl = document.getElementById("hotelPreviewImage");

  if (hotelNameEl) {
    hotelNameEl.textContent = bookingData.room.title || bookingData.hotel || "Fiesta Resort";
  }
  if (hotelLocationEl) {
    hotelLocationEl.textContent = bookingData.room.location || "Brgy. Ipil, Surigao City";
  }
  if (hotelImageEl) {
    hotelImageEl.src = bookingData.room.image 
      ? (bookingData.room.image.startsWith('/') ? bookingData.room.image : `/assets/${bookingData.room.image}`)
      : fallbackImage;
  }

  // Set room type, room_id and price - use from selected room
  window.bookingRoomType = bookingData.room.roomType || bookingData.room.type || bookingData.room.room_type || "Standard Room";
  window.bookingRoomId = bookingData.room.id;
  const pricePerNight = bookingData.room.price || bookingData.room.price_per_night || 2000;
  window.bookingPrice = pricePerNight;
  
  updateTotalPrice();
}

function initializeDatePicker() {
  const checkInInput = document.getElementById("checkInDate");
  const checkOutInput = document.getElementById("checkOutDate");
  
  if (!checkInInput || !checkOutInput) return;

  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const dayAfter = new Date(tomorrow);
  dayAfter.setDate(dayAfter.getDate() + 1);

  // Set minimum dates
  checkInInput.min = today.toISOString().split('T')[0];
  checkOutInput.min = tomorrow.toISOString().split('T')[0];

  // Set default dates
  checkInInput.value = tomorrow.toISOString().split('T')[0];
  checkOutInput.value = dayAfter.toISOString().split('T')[0];
  
  window.checkInDate = tomorrow;
  window.checkOutDate = dayAfter;

  // Check-in change handler
  checkInInput.addEventListener("change", function() {
    const checkIn = new Date(checkInInput.value + 'T00:00:00');
    window.checkInDate = checkIn;
    
    // Update check-out minimum date
    const nextDay = new Date(checkIn);
    nextDay.setDate(nextDay.getDate() + 1);
    checkOutInput.min = nextDay.toISOString().split('T')[0];
    
    // Auto-update check-out if it's before new minimum
    if (checkOutInput.value) {
      const currentCheckOut = new Date(checkOutInput.value + 'T00:00:00');
      if (currentCheckOut <= checkIn) {
        checkOutInput.value = nextDay.toISOString().split('T')[0];
        window.checkOutDate = nextDay;
      }
    }
    
    updateDaysAndPrice();
  });

  // Check-out change handler
  checkOutInput.addEventListener("change", function() {
    const checkOut = new Date(checkOutInput.value + 'T00:00:00');
    
    if (checkOut <= window.checkInDate) {
      if (window.showError) {
        window.showError("Check-out date must be after check-in date.");
      }
      // Reset to next day
      const nextDay = new Date(window.checkInDate);
      nextDay.setDate(nextDay.getDate() + 1);
      checkOutInput.value = nextDay.toISOString().split('T')[0];
      window.checkOutDate = nextDay;
    } else {
      window.checkOutDate = checkOut;
    }
    
    updateDaysAndPrice();
  });
}

function updateDaysAndPrice() {
  if (window.checkInDate && window.checkOutDate) {
    const days = Math.ceil((window.checkOutDate - window.checkInDate) / (1000 * 60 * 60 * 24));
    const daysCountEl = document.getElementById("daysCount");
    if (daysCountEl) {
      daysCountEl.textContent = days;
    }
    updateTotalPrice();
  }
}

function formatDate(date) {
  const months = [
    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
  ];
  const day = date.getDate();
  const month = months[date.getMonth()];
  return `${day} ${month}`;
}

function formatDateForInput(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function updateTotalPrice() {
  const days = parseInt(document.getElementById("daysCount").textContent, 10);
  const pricePerNight = window.bookingPrice || 2000;
  const total = days * pricePerNight;

  document.getElementById("totalPrice").textContent = total.toLocaleString();
  document.getElementById("priceDays").textContent = `${days} Days`;

  window.totalAmount = total;
  window.numberOfDays = days;
}

function setupEventListeners() {
  document.getElementById("continueToPayment").addEventListener("click", () => {
    // Validate dates
    if (!window.checkInDate || !window.checkOutDate) {
      if (window.showError) window.showError("Please select check-in and check-out dates.");
      return;
    }

    if (window.checkOutDate <= window.checkInDate) {
      if (window.showError) window.showError("Check-out date must be after check-in date.");
      return;
    }

    goToStep(2);
    updatePaymentInfo();
  });

  const cancelBtn = document.getElementById("cancelBooking");
  const cancelBookingModal = document.getElementById("cancelBookingModal");
  const cancelBookingModalConfirmBtn = document.getElementById("cancelBookingModalConfirmBtn");
  const cancelBookingModalCancelBtn = document.getElementById("cancelBookingModalCancelBtn");

  function showCancelBookingModal() {
    if (cancelBookingModal) {
      cancelBookingModal.classList.add("show");
    }
  }

  function hideCancelBookingModal() {
    if (cancelBookingModal) {
      cancelBookingModal.classList.remove("show");
    }
  }

  if (cancelBookingModalConfirmBtn) {
    cancelBookingModalConfirmBtn.addEventListener("click", () => {
      sessionStorage.removeItem("pendingBooking");
      hideCancelBookingModal();
      window.location.href = "/";
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

  if (cancelBtn) {
    cancelBtn.addEventListener("click", () => {
      showCancelBookingModal();
    });
  }

  document.getElementById("backToBooking").addEventListener("click", () => {
    goToStep(1);
  });

  document.getElementById("completePayment").addEventListener("click", async () => {
    if (validatePaymentForm()) {
      await processPayment();
    }
  });
}


function goToStep(stepNumber) {
  document.querySelectorAll(".booking-step").forEach((step) => {
    step.classList.add("hidden");
  });

  document.getElementById(`step${stepNumber}`).classList.remove("hidden");
  updateProgress(stepNumber);
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function updateProgress(currentStep) {
  const steps = document.querySelectorAll(".progress-step");
  const lines = document.querySelectorAll(".progress-line");

  steps.forEach((step, index) => {
    const stepNum = index + 1;
    if (stepNum < currentStep) {
      step.classList.add("completed");
      step.classList.remove("active");
    } else if (stepNum === currentStep) {
      step.classList.add("active");
      step.classList.remove("completed");
    } else {
      step.classList.remove("active", "completed");
    }
  });

  lines.forEach((line, index) => {
    if (index + 1 < currentStep) {
      line.classList.add("completed");
    } else {
      line.classList.remove("completed");
    }
  });
}

function updatePaymentInfo() {
  const bookingData = JSON.parse(sessionStorage.getItem("pendingBooking") || "{}");
  const days = window.numberOfDays || 2;
  const total = window.totalAmount || 2000;

  const paymentDaysEl = document.getElementById("paymentDays");
  const paymentHotelEl = document.getElementById("paymentHotel");
  const paymentLocationEl = document.getElementById("paymentLocation");
  const paymentTotalEl = document.getElementById("paymentTotal");

  if (paymentDaysEl) paymentDaysEl.textContent = days;
  if (paymentHotelEl) paymentHotelEl.textContent = bookingData.room?.title || bookingData.hotel || "Fiesta Resort";
  if (paymentLocationEl) paymentLocationEl.textContent = bookingData.room?.location || "Brgy. Ipil, Surigao City";
  if (paymentTotalEl) paymentTotalEl.textContent = total.toLocaleString();
}

function validatePaymentForm() {
  // All fields are now optional, but if provided, validate format
  const contactNumber = document.getElementById("gcashNumber").value.trim();
  const paymentMethod = document.getElementById("bankName").value;
  const specialRequests = document.getElementById("validationDate").value.trim();

  // If contact number is provided, validate format (Philippine numbers)
  if (contactNumber) {
    const phoneRegex = /^(09|\+639)\d{9}$/;
    const cleanedNumber = contactNumber.replace(/\s+/g, '');
    if (!phoneRegex.test(cleanedNumber)) {
      if (window.showError) window.showError("Please enter a valid contact number (09XXXXXXXXX or +639XXXXXXXXX)");
      return false;
    }
  }

  // All fields are optional, so validation always passes
  return true;
}

async function processPayment() {
  const payBtn = document.getElementById("completePayment");
  const originalText = payBtn.textContent;
  payBtn.textContent = "Processing...";
  payBtn.disabled = true;

  try {
    const bookingData = JSON.parse(sessionStorage.getItem("pendingBooking") || "{}");
    const user = window.laravelAuth?.user;

    if (!user) {
      throw new Error("User not authenticated");
    }

    if (!window.checkInDate || !window.checkOutDate) {
      throw new Error("Please select check-in and check-out dates");
    }

    if (!window.bookingRoomType) {
      throw new Error("Room type not specified");
    }

    if (!window.bookingRoomId) {
      throw new Error("Room not selected. Please go back and pick a specific room.");
    }

    const paymentMethod = document.getElementById("bankName").value;
    const contactNumber = document.getElementById("gcashNumber").value.trim();
    const specialRequests = document.getElementById("validationDate").value.trim();
    
    // Build notes from optional fields
    const notesParts = [];
    if (specialRequests) {
      notesParts.push(specialRequests);
    }
    
    const bookingPayload = {
      room_type: window.bookingRoomType,
      room_id: window.bookingRoomId,
      check_in: formatDateForInput(window.checkInDate),
      check_out: formatDateForInput(window.checkOutDate),
      guest_phone: contactNumber || user.phone || null,
      payment_method: paymentMethod || null,
      payment_number: contactNumber || null,
      notes: notesParts.length > 0 ? notesParts.join("\n") : null,
    };

    const response = await fetch(apiBaseUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": getCsrfToken(),
      },
      body: JSON.stringify(bookingPayload),
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || "Failed to process booking");
    }

    if (window.showSuccess) {
      window.showSuccess(result.message || "Booking submitted successfully!");
    }

    // Clear pending booking
    sessionStorage.removeItem("pendingBooking");

    // Go to success step
    goToStep(3);
  } catch (error) {
    console.error("Error processing booking:", error);
    if (window.showError) {
      window.showError(error.message || "Failed to process booking. Please try again.");
    }
    payBtn.textContent = originalText;
    payBtn.disabled = false;
  }
}

updateProgress(1);
