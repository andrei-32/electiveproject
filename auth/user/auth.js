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
            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();
            
            if (data.success) {
                window.location.href = '../../multiplayer.html';
            } else {
                showMessage(loginForm, data.message, 'error', data.error_code);
                // Clear password field on error
                loginForm.querySelector('input[type="password"]').value = '';
            }
        } catch (error) {
            console.error('Login error:', error);
            showMessage(loginForm, 'Network error occurred. Please try again.', 'error', 'NETWORK_ERROR');
        }
    });

    // Register form submission
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = registerForm.querySelector('input[type="text"]').value;
        const password = registerForm.querySelectorAll('input[type="password"]')[0].value;
        const confirmPassword = registerForm.querySelectorAll('input[type="password"]')[1].value;

        if (password !== confirmPassword) {
            showMessage(registerForm, 'Passwords do not match', 'error', 'PASSWORD_MISMATCH');
            return;
        }

        try {
            const response = await fetch('register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();
            
            if (data.success) {
                showMessage(registerForm, 'Registration successful! Please login.', 'success');
                // Clear all fields
                registerForm.reset();
                // Switch to login tab
                document.querySelector('[data-tab="login"]').click();
            } else {
                showMessage(registerForm, data.message, 'error', data.error_code);
                // Clear password fields on error
                registerForm.querySelectorAll('input[type="password"]').forEach(input => input.value = '');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showMessage(registerForm, 'Network error occurred. Please try again.', 'error', 'NETWORK_ERROR');
        }
    });

    function showMessage(form, message, type, errorCode = null) {
        // Remove any existing message
        const existingMessage = form.querySelector('.error-message, .success-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Create and append new message
        const messageElement = document.createElement('div');
        messageElement.className = `${type}-message`;
        
        // Add error code if present
        if (errorCode) {
            messageElement.textContent = `(${errorCode}) ${message}`;
        } else {
            messageElement.textContent = message;
        }
        
        form.appendChild(messageElement);

        // Remove message after 5 seconds for errors, 3 seconds for success
        setTimeout(() => {
            messageElement.remove();
        }, type === 'error' ? 5000 : 3000);
    }
}); 