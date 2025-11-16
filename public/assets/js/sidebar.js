// Enhanced sidebar functionality with smooth animations
document.addEventListener('DOMContentLoaded', () => {
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  const mainContent = document.querySelector('.main-content');

  // Toggle sidebar functionality
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleSidebar();
    });
  }

  // Overlay click to close sidebar
  if (overlay) {
    overlay.addEventListener('click', () => {
      closeSidebar();
    });
  }

  // Close sidebar on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  // Handle window resize
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  function toggleSidebar() {
    if (sidebar && overlay) {
      const isOpen = sidebar.classList.contains('open');
      
      if (isOpen) {
        closeSidebar();
      } else {
        openSidebar();
      }
    }
  }

  function openSidebar() {
    if (sidebar && overlay) {
      sidebar.classList.add('open');
      overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeSidebar() {
    if (sidebar && overlay) {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
      document.body.style.overflow = 'auto';
    }
  }

  // Add smooth scrolling to navigation links
  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      // Add loading state
      const originalText = link.innerHTML;
      link.style.opacity = '0.7';
      
      // Restore after a short delay (visual feedback)
      setTimeout(() => {
        link.style.opacity = '1';
      }, 200);
    });
  });

  // Add active state management
  function setActiveNavLink() {
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
      const href = link.getAttribute('href');
      if (href && href.includes(`page=${currentPage}`)) {
        link.style.backgroundColor = '#a7e3a7';
        link.style.transform = 'translateX(5px)';
      }
    });
  }

  // Set active nav link on page load
  setActiveNavLink();
});