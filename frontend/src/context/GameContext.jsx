import React, { createContext, useState, useEffect, useContext } from 'react';
import api from '../services/api';
import echo from '../services/echo';
import { AuthContext } from './AuthContext';

export const GameContext = createContext(null);

export const GameProvider = ({ children }) => {
    const { user } = useContext(AuthContext);
    const [game, setGame] = useState(null);
    const [myHand, setMyHand] = useState(null);
    const [loading, setLoading] = useState(false);

    // Refresh game state fully
    const fetchGameState = async (gameId) => {
        setLoading(true);
        try {
            const response = await api.get(`/games/${gameId}`);
            setGame(response.data.game);
        } catch (error) {
            console.error("Failed to fetch game state", error);
        } finally {
            setLoading(false);
        }
    };

    // Subscriptions
    useEffect(() => {
        if (!game || !user) return;

        // Subscribe to Game State updates
        const gameChannel = echo.private(`game.${game.id}`)
            .listen('GameStateUpdated', (e) => {
                console.log('GameStateUpdated', e);
                setGame(e.game); // Update full state
            })
            .listen('BetPlaced', (e) => {
                console.log('BetPlaced', e);
                // State usually refreshed by GameStateUpdated, but can do optimistic UI here
            })
            .listen('PlayerFolded', (e) => {
               console.log('PlayerFolded', e);
            })
            .listen('RoundSettled', (e) => {
                console.log('RoundSettled', e);
                // Trigger modal or similar side-effects in components
            })
            .listen('SideBetResult', (e) => {
                console.log('SideBetResult', e);
            });

        // Find my game player record to subscribe to private hand deals
        const mePlayer = game.game_players?.find(p => p.user_id === user.id);
        
        let playerChannel = null;
        if (mePlayer) {
            // Restore hand from initial load if present
            if (mePlayer.hand && mePlayer.hand.length > 0) {
               setMyHand(mePlayer.hand);
            }

            playerChannel = echo.private(`player.${mePlayer.id}`)
                .listen('CardsDealt', (e) => {
                    console.log('CardsDealt', e);
                    setMyHand(e.hand);
                });
        }

        return () => {
            gameChannel.stopListening('GameStateUpdated');
            gameChannel.stopListening('BetPlaced');
            gameChannel.stopListening('PlayerFolded');
            gameChannel.stopListening('RoundSettled');
            gameChannel.stopListening('SideBetResult');
            echo.leave(`game.${game.id}`);
            
            if (mePlayer) {
                playerChannel?.stopListening('CardsDealt');
                echo.leave(`player.${mePlayer.id}`);
            }
        };
    }, [game?.id, user?.id]);


    // API Actions
    const createGame = async (settings) => {
        const res = await api.post('/games', { settings });
        setGame(res.data);
        return res.data;
    };

    const joinGame = async (gameId, buyIn) => {
        await api.post(`/games/${gameId}/join`, { buy_in: buyIn });
        await fetchGameState(gameId);
    };

    const startRound = async () => {
        if (!game) return;
        setMyHand(null); // clear old cards
        await api.post(`/games/${game.id}/rounds`);
    };

    const placeBet = async (type, amount) => {
        if (!game) return;
        const currentRound = game.rounds[0];
        if (!currentRound) return;
        
        const payload = { type };
        if (amount) payload.amount = amount;
        
        await api.post(`/rounds/${currentRound.id}/bet`, payload);
    };

    const call = async () => {
         if (!game) return;
         const currentRound = game.rounds[0];
         if (!currentRound) return;
         await api.post(`/rounds/${currentRound.id}/call`);
    };

    const fold = async () => {
         if (!game) return;
         const currentRound = game.rounds[0];
         if (!currentRound) return;
         await api.post(`/rounds/${currentRound.id}/fold`);
    };

    const settleRound = async () => {
         if (!game) return;
         const currentRound = game.rounds[0];
         if (!currentRound) return;
         await api.post(`/games/${game.id}/rounds/${currentRound.id}/settle`);
    };

    const addFunds = async (playerId, amount) => {
         if (!game) return;
         await api.post(`/games/${game.id}/add-funds`, { player_id: playerId, amount });
    };

    const getCallAmount = async () => {
         if (!game) return 0;
         const currentRound = game.rounds[0];
         if (!currentRound) return 0;
         const res = await api.get(`/rounds/${currentRound.id}/call-amount`);
         return res.data.amount;
    };

    return (
        <GameContext.Provider value={{ 
            game, 
            myHand, 
            loading, 
            fetchGameState, 
            createGame, 
            joinGame,
            startRound,
            placeBet,
            call,
            fold,
            settleRound,
            addFunds,
            getCallAmount
        }}
        >
            {children}
        </GameContext.Provider>
    );
};
