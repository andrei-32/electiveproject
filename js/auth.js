document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const errorContainer = document.getElementById('error-container');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch('user/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = '../game_selection.html';
                } else {
                    addErrorMessage(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                addErrorMessage('An error occurred. Please try again.');
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                addErrorMessage('Passwords do not match');
                return;
            }

            try {
                const response = await fetch('user/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = '../game_selection.html';
                } else {
                    addErrorMessage(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                addErrorMessage('An error occurred. Please try again.');
            }
        });
    }

    function addErrorMessage(message) {
        const div = document.createElement("div");
        div.classList.add("error-message")

        div.textContent = message;

        errorContainer.appendChild(div)
    }
}); 

