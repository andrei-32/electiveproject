document.addEventListener('DOMContentLoaded', () => {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const authForms = document.querySelectorAll('.auth-form');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    // Tab switching logic
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            
            // Update active tab button
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Show corresponding form
            authForms.forEach(form => {
                form.classList.remove('active');
                if (form.id === `${tab}Form`) {
                    form.classList.add('active');
                }
            });
        });
    });

    // Login form submission
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = loginForm.querySelector('input[type="text"]').value;
        const password = loginForm.querySelector('input[type="password"]').value;

        try {
            const response = await fetch('auth/user/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();
            
            if (data.success) {
                window.location.href = 'multiplayer.html';
            } else {
                showMessage(loginForm, data.message, 'error');
            }
        } catch (error) {
            showMessage(loginForm, 'An error occurred. Please try again.', 'error');
        }
    });

    // Register form submission
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = registerForm.querySelector('input[type="text"]').value;
        const password = registerForm.querySelectorAll('input[type="password"]')[0].value;
        const confirmPassword = registerForm.querySelectorAll('input[type="password"]')[1].value;

        if (password !== confirmPassword) {
            showMessage(registerForm, 'Passwords do not match', 'error');
            return;
        }

        try {
            const response = await fetch('auth/user/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();
            
            if (data.success) {
                showMessage(registerForm, 'Registration successful! Please login.', 'success');
                // Switch to login tab
                document.querySelector('[data-tab="login"]').click();
            } else {
                showMessage(registerForm, data.message, 'error');
            }
        } catch (error) {
            showMessage(registerForm, 'An error occurred. Please try again.', 'error');
        }
    });

    function showMessage(form, message, type) {
        // Remove any existing message
        const existingMessage = form.querySelector('.error-message, .success-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Create and append new message
        const messageElement = document.createElement('div');
        messageElement.className = `${type}-message`;
        messageElement.textContent = message;
        form.appendChild(messageElement);

        // Remove message after 3 seconds
        setTimeout(() => {
            messageElement.remove();
        }, 3000);
    }
}); 