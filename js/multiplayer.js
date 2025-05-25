document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in
    fetch('auth/user/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.logged_in) {
                window.location.href = 'index.html';
            }
        });

    // Game elements
    const controlsContainer = document.getElementById('controlsContainer')
    const createRoomBtn = document.getElementById('createRoomBtn');
    const roomLink = document.getElementById('roomLink');
    const copyLinkBtn = document.getElementById('copyLinkBtn');
    const roomInfo = document.getElementById('infoContainer');
    const joinRoomLink = document.getElementById('joinRoomLink')
    const joinRoomButton = document.getElementById('joinRoomBtn')

    joinRoomButton.addEventListener('click', (e) => {
        let roomCode = joinRoomLink.value.trim().toUpperCase();
        if (!roomCode) {
            joinRoomLink.setCustomValidity("Please enter a room code.")
            joinRoomLink.reportValidity();
            return
        }

        if (!/^[A-Za-z0-9]{6}$/.test(roomCode)) {
            joinRoomLink.setCustomValidity("Invalid room code.")
            joinRoomLink.reportValidity();
            return
        }

        window.location.href = `/multiplayer.html?room=${roomCode}`
    })

    // Check for room ID in URL
    const urlParams = new URLSearchParams(window.location.search);
    const roomIdFromUrl = urlParams.get('room');
    if (roomIdFromUrl) {
        // Show room info and start polling
        roomInfo.style.display = 'flex';
        controlsContainer.style.display = 'none';
        roomLink.value = roomIdFromUrl;
        // Check if the user is already a player in the room
        fetch(`auth/game/get_game_state.php?room_id=${roomIdFromUrl}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (
                        data.game_state.player1_id == data.game_state.current_user_id ||
                        data.game_state.player2_id == data.game_state.current_user_id
                    ) {
                        window.location.href = `game.html?room=${roomIdFromUrl}`;
                    } else {
                        joinRoom(roomIdFromUrl);
                    }
                } else {
                    joinRoom(roomIdFromUrl);
                }
            });
    }

    // Create room
    createRoomBtn.addEventListener('click', createRoom);
    copyLinkBtn.addEventListener('click', copyRoomCode);

    async function createRoom() {
        try {
            const response = await fetch('auth/game/create_room.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                // Show room info and start polling
                roomInfo.style.display = 'block';
                controlsContainer.style.display = 'none';
                
                // Just show the room code
                roomLink.value = data.room_id;
                
                // Update the URL in the browser without reloading
                window.history.pushState({}, '', `?room=${data.room_id}`);
                pollForOpponent(data.room_id);
            } else {
                showError(data.message || 'Failed to create room');
            }
        } catch (error) {
            console.error('Error creating room:', error);
            showError('Failed to create room. Please try again.');
        }
    }

    async function joinRoom(roomId) {
        try {
            const response = await fetch('auth/game/join_room.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ room_id: roomId })
            });

            const data = await response.json();
            
            if (data.success) {
                // Redirect to game page
                window.location.href = `game.html?room=${roomId}`;
            } else {
                showError(data.message || 'Failed to join room');
            }
        } catch (error) {
            console.error('Error joining room:', error);
            showError('Failed to join room. Please try again.');
        }
    }

    async function pollForOpponent(roomId) {
        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`auth/game/get_game_state.php?room_id=${roomId}`);
                const data = await response.json();
                console.log('Polling response:', data); // Debug log

                if (data.success) {
                    if (data.game_state.status === 'playing') {
                        clearInterval(pollInterval);
                        window.location.href = `game.html?room=${roomId}`;
                    }
                } else {
                    clearInterval(pollInterval);
                    alert(data.message || 'Error joining game. Please refresh.');
                }
            } catch (error) {
                clearInterval(pollInterval);
                alert('Error polling for opponent. Please refresh.');
                console.error('Error polling for opponent:', error);
            }
        }, 1000);
    }

    function copyRoomCode() {
        roomLink.select();
        document.execCommand('copy');
        copyLinkBtn.textContent = 'Copied!';
        setTimeout(() => {
            copyLinkBtn.textContent = 'Copy Code';
        }, 2000);
    }

    function showError(message) {
        alert(message);
    }
}); 