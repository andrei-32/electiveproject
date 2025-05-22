document.addEventListener('DOMContentLoaded', () => {
    const roomId = new URLSearchParams(window.location.search).get('room');
    if (!roomId) {
        window.location.href = 'multiplayer.html';
        return;
    }

    // DOM Elements
    const roomIdSpan = document.getElementById('roomId');
    const gameStatus = document.getElementById('gameStatus');
    const choiceButtons = document.querySelectorAll('.choice-btn');
    const playerChoice = document.getElementById('playerChoice');
    const opponentChoice = document.getElementById('opponentChoice');
    const roundResult = document.getElementById('roundResult');
    const currentRound = document.getElementById('currentRound');
    const playerScore = document.getElementById('playerScore');
    const opponentScore = document.getElementById('opponentScore');
    const newRoundBtn = document.getElementById('newRoundBtn');
    const leaveGameBtn = document.getElementById('leaveGameBtn');
    const winsNeeded = document.getElementById('winsNeeded');
    const resultPrompt = document.getElementById('resultPrompt');

    // Game state
    let gameState = {
        round: 1,
        playerScore: 0,
        opponentScore: 0,
        playerChoice: null,
        opponentChoice: null,
        roundComplete: false,
        gameComplete: false
    };
    let isPlayer1 = null;

    // Add rematch state
    let rematchRequested = false;
    let opponentRematchRequested = false;

    // Add a rematch button
    const rematchBtn = document.createElement('button');
    rematchBtn.id = 'rematchBtn';
    rematchBtn.className = 'button';
    rematchBtn.textContent = 'Rematch';
    rematchBtn.style.display = 'none';
    document.querySelector('.game-controls').appendChild(rematchBtn);

    // Initialize game
    roomIdSpan.textContent = roomId;
    updateGameState();

    // Event Listeners
    choiceButtons.forEach(button => {
        button.addEventListener('click', () => makeChoice(button.dataset.choice));
    });

    newRoundBtn.addEventListener('click', () => {
        if (gameState.gameComplete) {
            // Reload the page to start a new game
            window.location.href = 'multiplayer.html';
        } else {
            startNewRound();
        }
    });
    leaveGameBtn.addEventListener('click', leaveGame);

    rematchBtn.addEventListener('click', requestRematch);

    // Poll for game updates
    setInterval(checkGameState, 500);

    // Functions
    function makeChoice(choice) {
        if (gameState.roundComplete || gameState.gameComplete || gameState.playerChoice) return;

        fetch('auth/game/make_move.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_id: roomId,
                choice: choice
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                gameState.playerChoice = choice;
                // Disable all choice buttons after making a choice
                choiceButtons.forEach(button => {
                    button.disabled = true;
                    if (button.dataset.choice === choice) {
                        button.classList.add('selected');
                    } else {
                        button.classList.remove('selected');
                    }
                });
                updateGameState();
                // Immediately check game state after making a choice
                checkGameState();
            } else {
                alert(data.message || 'Failed to make move');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to make move');
        });
    }

    function checkGameState() {
        fetch(`auth/game/get_game_state.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const oldState = { ...gameState };
                    updateGameState(data.game_state);
                    
                    // If round just completed, show the result immediately
                    if (data.game_state.round_complete && !oldState.round_complete) {
                        const result = determineWinner(
                            isPlayer1 ? data.game_state.player1_choice : data.game_state.player2_choice,
                            isPlayer1 ? data.game_state.player2_choice : data.game_state.player1_choice
                        );
                        showResultPrompt(result);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function updateGameState(state = null) {
        if (state) {
            // Determine if current user is player1 or player2
            if (typeof state.current_user_id !== 'undefined') {
                isPlayer1 = (state.current_user_id == state.player1_id);
                gameState.playerChoice = isPlayer1 ? state.player1_choice : state.player2_choice;
                gameState.opponentChoice = isPlayer1 ? state.player2_choice : state.player1_choice;
                gameState.playerScore = isPlayer1 ? state.player1_score : state.player2_score;
                gameState.opponentScore = isPlayer1 ? state.player2_score : state.player1_score;
            }
            gameState = { ...gameState, ...state };
            // Normalize roundComplete to always be a boolean
            gameState.roundComplete = !!(state.round_complete || state.roundComplete);
        }

        // Check for game completion (first to 3 wins)
        if (gameState.playerScore >= 3 || gameState.opponentScore >= 3) {
            gameState.gameComplete = true;
        }

        // Update UI
        currentRound.textContent = gameState.round;
        playerScore.textContent = gameState.playerScore;
        opponentScore.textContent = gameState.opponentScore;
        winsNeeded.textContent = '3'; // First to 3 wins

        // Update choices
        updateChoiceDisplay(playerChoice, gameState.playerChoice, false);
        updateChoiceDisplay(opponentChoice, gameState.opponentChoice, true);

        // Update buttons
        choiceButtons.forEach(button => {
            // Disable buttons if:
            // 1. Round is complete
            // 2. Game is complete
            // 3. Player has already made a choice
            const shouldDisable = gameState.roundComplete || 
                                gameState.gameComplete || 
                                gameState.playerChoice !== null;
            
            button.disabled = shouldDisable;
            
            if (shouldDisable) {
                button.classList.remove('selected');
            } else {
                button.classList.toggle('selected', button.dataset.choice === gameState.playerChoice);
            }
        });

        // Show/hide appropriate buttons based on game state
        if (gameState.gameComplete) {
            newRoundBtn.style.display = 'none';
            newRoundBtn.disabled = true;
            rematchBtn.style.display = '';
            rematchBtn.disabled = rematchRequested;
            rematchBtn.textContent = opponentRematchRequested ? 'Opponent wants a rematch!' : (rematchRequested ? 'Waiting for opponent...' : 'Rematch');
        } else if (gameState.roundComplete) {
            newRoundBtn.style.display = '';
            newRoundBtn.disabled = false;
            rematchBtn.style.display = 'none';
        } else {
            newRoundBtn.style.display = 'none';
            newRoundBtn.disabled = true;
            rematchBtn.style.display = 'none';
        }

        // Update status and result messages
        if (gameState.gameComplete) {
            gameStatus.textContent = 'Game Over';
            roundResult.textContent = getGameResult();
            showResultPrompt('');
        } else if (gameState.roundComplete) {
            gameStatus.textContent = 'Round Complete';
            const result = determineWinner(gameState.playerChoice, gameState.opponentChoice);
            const resultMessage = getRoundResult();
            roundResult.innerHTML = `
                <div class="round-result-message">${resultMessage}</div>
                <div class="round-choices">
                    <div class="choice-result">
                        <span class="player-name">You:</span>
                        <span class="choice">${gameState.playerChoice ? gameState.playerChoice.charAt(0).toUpperCase() + gameState.playerChoice.slice(1) : ''}</span>
                    </div>
                    <div class="choice-result">
                        <span class="player-name">Opponent:</span>
                        <span class="choice">${gameState.opponentChoice ? gameState.opponentChoice.charAt(0).toUpperCase() + gameState.opponentChoice.slice(1) : ''}</span>
                    </div>
                </div>
            `;
            showResultPrompt(result);
        } else if (gameState.playerChoice) {
            gameStatus.textContent = 'Waiting for opponent...';
            roundResult.textContent = 'Waiting for opponent\'s choice...';
            showResultPrompt('');
        } else {
            gameStatus.textContent = 'Make your choice';
            roundResult.textContent = 'Choose rock, paper, or scissors';
            showResultPrompt('');
        }

        console.log('gameState:', gameState);
    }

    function updateChoiceDisplay(element, choice, isOpponent = false) {
        const img = element.querySelector('img');
        let messageElem = element.querySelector('.opponent-pick-message');
        if (isOpponent) {
            if (!messageElem) {
                messageElem = document.createElement('div');
                messageElem.className = 'opponent-pick-message';
                element.appendChild(messageElem);
            }
            if (gameState.roundComplete && choice) {
                img.src = `images/game/${choice}.png`;
                img.alt = choice.charAt(0).toUpperCase() + choice.slice(1);
                messageElem.textContent = '';
            } else if (choice) {
                img.src = 'images/game/checkmark.png';
                img.alt = 'Picked';
                messageElem.textContent = 'Opponent made a pick';
            } else {
                img.src = 'images/game/questionmark.png';
                img.alt = '?';
                messageElem.textContent = '';
            }
        } else {
            if (messageElem) messageElem.textContent = '';
            if (choice) {
                img.src = `images/game/${choice}.png`;
                img.alt = choice.charAt(0).toUpperCase() + choice.slice(1);
            } else {
                img.src = 'images/game/questionmark.png';
                img.alt = '?';
            }
        }
    }

    function startNewRound() {
        if (gameState.gameComplete) return;

        fetch('auth/game/start_new_round.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                room_id: roomId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset local state for new round
                gameState.playerChoice = null;
                gameState.opponentChoice = null;
                gameState.roundComplete = false;
                // Re-enable choice buttons
                choiceButtons.forEach(button => {
                    button.disabled = false;
                    button.classList.remove('selected');
                });
                updateChoiceDisplay(playerChoice, null, false);
                updateChoiceDisplay(opponentChoice, null, true);
                updateGameState();
                showResultPrompt('');
            } else {
                alert(data.message || 'Failed to start new round');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to start new round');
        });
    }

    function leaveGame() {
        if (confirm('Are you sure you want to leave the game?')) {
            window.location.href = 'multiplayer.html';
        }
    }

    function getRoundResult() {
        if (!gameState.playerChoice || !gameState.opponentChoice) return 'Waiting for choices...';

        const result = determineWinner(gameState.playerChoice, gameState.opponentChoice);
        return result === 'tie' ? 'It\'s a tie!' :
               result === 'player' ? 'You won this round!' :
               'Opponent won this round!';
    }

    function getGameResult() {
        if (gameState.playerScore > gameState.opponentScore) {
            // Update game result for win
            fetch('auth/game/update_game_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_id: roomId,
                    result: 'win'
                })
            }).catch(error => console.error('Error updating game result:', error));
            
            return 'Congratulations! You won the game!';
        } else if (gameState.opponentScore > gameState.playerScore) {
            // Update game result for loss
            fetch('auth/game/update_game_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_id: roomId,
                    result: 'loss'
                })
            }).catch(error => console.error('Error updating game result:', error));
            
            return 'Game Over! Opponent won the game!';
        } else {
            // Update game result for tie
            fetch('auth/game/update_game_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_id: roomId,
                    result: 'tie'
                })
            }).catch(error => console.error('Error updating game result:', error));
            
            return 'The game ended in a tie!';
        }
    }

    function determineWinner(player, opponent) {
        if (player === opponent) return 'tie';
        
        const winningCombos = {
            rock: 'scissors',
            paper: 'rock',
            scissors: 'paper'
        };

        return winningCombos[player] === opponent ? 'player' : 'opponent';
    }

    function showResultPrompt(result) {
        if (result === 'tie') {
            resultPrompt.textContent = 'Tie!';
            resultPrompt.style.color = '#FFD700'; // Gold color for ties
            resultPrompt.style.fontSize = '24px';
            resultPrompt.style.fontWeight = 'bold';
        } else if (result === 'player') {
            resultPrompt.textContent = 'You Won!';
            resultPrompt.style.color = '#28a745'; // Green for wins
            resultPrompt.style.fontSize = '24px';
            resultPrompt.style.fontWeight = 'bold';
        } else if (result === 'opponent') {
            resultPrompt.textContent = 'You Lost!';
            resultPrompt.style.color = '#dc3545'; // Red for losses
            resultPrompt.style.fontSize = '24px';
            resultPrompt.style.fontWeight = 'bold';
        } else {
            resultPrompt.textContent = '';
            resultPrompt.style.fontSize = '';
            resultPrompt.style.fontWeight = '';
        }
    }

    function requestRematch() {
        rematchRequested = true;
        rematchBtn.disabled = true;
        rematchBtn.textContent = 'Waiting for opponent...';
        fetch('auth/game/rematch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: roomId })
        });
    }

    function checkRematchState() {
        if (!gameState.gameComplete) return;
        fetch(`auth/game/get_game_state.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.game_state.rematch) {
                    // Reset everything for a new game
                    rematchRequested = false;
                    opponentRematchRequested = false;
                    rematchBtn.style.display = 'none';
                    newRoundBtn.style.display = '';
                    gameState = {
                        round: 1,
                        playerScore: 0,
                        opponentScore: 0,
                        playerChoice: null,
                        opponentChoice: null,
                        roundComplete: false,
                        gameComplete: false
                    };
                    updateGameState();
                } else if (data.success && data.game_state.opponent_rematch) {
                    opponentRematchRequested = true;
                    rematchBtn.textContent = 'Opponent wants a rematch!';
                    rematchBtn.disabled = false;
                }
            });
    }
}); 