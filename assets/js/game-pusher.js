// Initialize Pusher
const pusher = new Pusher('8b2b38eef8daa1db619b', {
    cluster: 'ap1'
});

// Game channel events
class GamePusher {
    constructor(roomId) {
        this.roomId = roomId;
        this.channel = pusher.subscribe(`game-room-${roomId}`);
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Listen for opponent's move
        this.channel.bind('opponent-move', (data) => {
            console.log('Opponent made a move:', data);
            // Update UI to show opponent's choice
            this.handleOpponentMove(data);
        });

        // Listen for game state updates
        this.channel.bind('game-state-update', (data) => {
            console.log('Game state updated:', data);
            // Update game state (scores, round, etc.)
            this.handleGameStateUpdate(data);
        });

        // Listen for opponent joining
        this.channel.bind('opponent-joined', (data) => {
            console.log('Opponent joined:', data);
            // Update UI to show opponent has joined
            this.handleOpponentJoined(data);
        });

        // Listen for rematch requests
        this.channel.bind('rematch-request', (data) => {
            console.log('Rematch requested:', data);
            // Show rematch request UI
            this.handleRematchRequest(data);
        });
    }

    // Event handlers
    handleOpponentMove(data) {
        // Update UI to show opponent's choice
        const opponentChoice = document.querySelector('.opponent-choice');
        if (opponentChoice) {
            opponentChoice.textContent = `Opponent chose: ${data.choice}`;
        }
    }

    handleGameStateUpdate(data) {
        // Update scores
        const player1Score = document.querySelector('.player1-score');
        const player2Score = document.querySelector('.player2-score');
        if (player1Score) player1Score.textContent = data.player1_score;
        if (player2Score) player2Score.textContent = data.player2_score;

        // Update round status
        const roundStatus = document.querySelector('.round-status');
        if (roundStatus) {
            roundStatus.textContent = `Round ${data.round}`;
        }

        // Update game status
        if (data.status === 'completed') {
            this.handleGameComplete(data);
        }
    }

    handleOpponentJoined(data) {
        const waitingMessage = document.querySelector('.waiting-message');
        if (waitingMessage) {
            waitingMessage.textContent = `Opponent ${data.username} has joined!`;
        }
        // Enable game controls
        this.enableGameControls();
    }

    handleRematchRequest(data) {
        const rematchPrompt = document.querySelector('.rematch-prompt');
        if (rematchPrompt) {
            rematchPrompt.style.display = 'block';
            rematchPrompt.innerHTML = `
                <p>${data.username} wants a rematch!</p>
                <button onclick="acceptRematch()">Accept</button>
                <button onclick="declineRematch()">Decline</button>
            `;
        }
    }

    handleGameComplete(data) {
        const gameResult = document.querySelector('.game-result');
        if (gameResult) {
            let resultMessage = '';
            if (data.winner === 'player1') {
                resultMessage = 'Player 1 wins!';
            } else if (data.winner === 'player2') {
                resultMessage = 'Player 2 wins!';
            } else {
                resultMessage = 'It\'s a tie!';
            }
            gameResult.textContent = resultMessage;
        }
    }

    enableGameControls() {
        const gameControls = document.querySelector('.game-controls');
        if (gameControls) {
            gameControls.style.display = 'block';
        }
    }

    // Method to trigger events
    static triggerMove(roomId, choice) {
        fetch('auth/game/make_move.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_id: roomId,
                choice: choice
            })
        });
    }

    static requestRematch(roomId) {
        fetch('auth/game/rematch.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_id: roomId
            })
        });
    }
}

// Export for use in other files
window.GamePusher = GamePusher; 