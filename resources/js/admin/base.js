const body = document.body;

// Check if user is admin on page load (fallback check - server-side middleware is primary)
document.addEventListener("DOMContentLoaded", function () {
  // Check Laravel auth first, then fall back to localStorage (for dummy users)
  const isLoggedIn = window.laravelAuth?.isAuthenticated || localStorage.getItem("isLoggedIn") === "true";
  const userRole = window.laravelAuth?.user?.role || localStorage.getItem("userRole");
  
  if (!isLoggedIn || userRole !== "admin") {
    if (window.showError) {
      window.showError("Access denied. Admin privileges required.");
    }
    setTimeout(() => {
      window.location.href = "/";
    }, 1000);
    return;
  }
});

const userBtn = document.getElementById("userBtn");
const userMenu = document.getElementById("userMenu");

if (userBtn && userMenu) {
  userBtn.addEventListener("click", (event) => {
    event.stopPropagation();
    userMenu.classList.toggle("show");
  });
}

document.addEventListener("click", (event) => {
  if (
    userMenu &&
    userMenu.classList.contains("show") &&
    !userMenu.contains(event.target) &&
    event.target !== userBtn
  ) {
    userMenu.classList.remove("show");
  }
});

const logoutModal = document.getElementById("logoutModal");
const logoutCancelBtn = document.getElementById("logoutModalCancelBtn");
const logoutConfirmBtn = document.getElementById("logoutModalConfirmBtn");
const logoutButtons = document.querySelectorAll("[data-trigger-logout]");

// Function to show logout modal
function showLogoutModal() {
  if (logoutModal) {
    logoutModal.classList.add("show");
    // Close user menu if open
    if (userMenu) {
      userMenu.classList.remove("show");
    }
  }
}

// Function to hide logout modal
function hideLogoutModal() {
  if (logoutModal) {
    logoutModal.classList.remove("show");
  }
}

// Function to perform logout
function performLogout() {
  // If using Laravel auth, submit logout form
  if (window.laravelAuth?.isAuthenticated) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = window.laravelAuth.logoutUrl || "/logout";
    
    const csrfToken = document.createElement("input");
    csrfToken.type = "hidden";
    csrfToken.name = "_token";
    csrfToken.value = window.laravelAuth.csrfToken || "";
    form.appendChild(csrfToken);
    
    document.body.appendChild(form);
    form.submit();
    return;
  }
  
  // Otherwise, clear localStorage (for dummy users)
  // Clear localStorage (dummy data authentication)
  localStorage.removeItem("isLoggedIn");
  localStorage.removeItem("userEmail");
  localStorage.removeItem("userName");
  localStorage.removeItem("userRole");
  localStorage.removeItem("lastLogin");
  localStorage.removeItem("rememberedEmail");
  localStorage.removeItem("rememberMe");

  // Redirect to login page
  window.location.href = "/login";
}

// Add click handlers to logout buttons
logoutButtons.forEach((btn) => {
  btn.addEventListener("click", (event) => {
    event.preventDefault();
    event.stopPropagation();
    showLogoutModal();
  });
});

// Cancel button handler
if (logoutCancelBtn) {
  logoutCancelBtn.addEventListener("click", () => {
    hideLogoutModal();
  });
}

// Confirm button handler
if (logoutConfirmBtn) {
  logoutConfirmBtn.addEventListener("click", () => {
    performLogout();
  });
}

// Close modal when clicking outside
if (logoutModal) {
  logoutModal.addEventListener("click", (event) => {
    if (event.target === logoutModal) {
      hideLogoutModal();
    }
  });
}

// Close modal on Escape key
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && logoutModal && logoutModal.classList.contains("show")) {
    hideLogoutModal();
  }
});

const dropdownItems = document.querySelectorAll(".dropdown-item");

dropdownItems.forEach((item) => {
  item.addEventListener("click", (event) => {
    if (item.tagName === "A") {
      return;
    }

    const id = item.getAttribute("id");
    if (id === "logoutBtn") {
      return;
    }

    const text = item.textContent.trim();

    if (text === "View all notifications") {
      if (window.showInfo) {
        window.showInfo("Notifications page will be implemented soon.");
      }
    } else if (text === "Account Preferences") {
      if (window.showInfo) {
        window.showInfo("Account preferences page will be implemented soon.");
      }
    } else {
      console.log("Clicked:", text);
    }

    if (notificationMenu) {
      notificationMenu.classList.remove("show");
    }
    if (userMenu) {
      userMenu.classList.remove("show");
    }
  });
});

