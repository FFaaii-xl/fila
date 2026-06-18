/**
 * Citroroso Theme Management
 * Shared JavaScript for theme toggle (landing & login pages)
 */

// Theme Management
function getTheme() {
    const saved = localStorage.getItem('theme');
    if (saved) return saved;
    // Auto-detect by time: 18:00 - 06:00 = dark
    const hour = new Date().getHours();
    return (hour < 6 || hour >= 18) ? 'dark' : 'light';
}

function applyTheme(theme) {
    const html = document.documentElement;
    const sunIcon = document.querySelector('.sun-icon');
    const moonIcon = document.querySelector('.moon-icon');
    
    if (theme === 'dark') {
        html.classList.add('dark');
        if (sunIcon) sunIcon.style.display = 'none';
        if (moonIcon) moonIcon.style.display = 'block';
    } else {
        html.classList.remove('dark');
        if (sunIcon) sunIcon.style.display = 'block';
        if (moonIcon) moonIcon.style.display = 'none';
    }
}

function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    const theme = isDark ? 'dark' : 'light';
    localStorage.setItem('theme', theme);
    applyTheme(theme);
}

// Password Toggle (login page only)
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    
    if (!passwordInput || !eyeIcon) return;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    applyTheme(getTheme());
});
