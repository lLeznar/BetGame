import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import styled from 'styled-components';
import { useAuth } from '../hooks/useAuth';
import { useGame } from '../hooks/useGame';

const Container = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  max-width: 800px;
  margin: 0 auto;
`;

const Header = styled.div`
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
`;

const Title = styled.h1`
  color: var(--color-gold);
  margin: 0;
`;

const Card = styled.div`
  background: var(--color-surface);
  padding: 30px;
  border-radius: var(--radius);
  border: 1px solid rgba(255,255,255,0.1);
  width: 100%;
  margin-bottom: 20px;
`;

const Input = styled.input`
  padding: 12px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.2);
  background: rgba(0,0,0,0.5);
  color: white;
  width: 100%;
  margin-bottom: 15px;
  font-size: 16px;
`;

const Button = styled.button`
  padding: 14px 24px;
  border-radius: 8px;
  font-weight: bold;
  background: ${props => props.$primary ? 'var(--color-gold)' : '#1976d2'};
  color: ${props => props.$primary ? 'black' : 'white'};
  border: none;
  font-size: 16px;
  width: 100%;
`;

export const LobbyPage = () => {
  const { user, logout } = useAuth();
  const { createGame, joinGame, fetchGameState } = useGame();
  const navigate = useNavigate();

  const [joinId, setJoinId] = useState('');
  const [buyIn, setBuyIn] = useState('');
  const [error, setError] = useState('');

  const handleCreate = async () => {
    try {
      const game = await createGame({ ante_amount: 5, max_debt_limit: 0, side_bets_enabled: true });
      navigate(`/game/${game.id}`);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create game');
    }
  };

  const handleJoin = async (e) => {
    e.preventDefault();
    try {
        await joinGame(joinId, parseFloat(buyIn) || 0);
        navigate(`/game/${joinId}`);
    } catch (err) {
        // if already joined or error, just try routing
        try {
            await fetchGameState(joinId);
            navigate(`/game/${joinId}`);
        } catch(e) {
            setError(err.response?.data?.message || 'Failed to join game');
        }
    }
  };

  return (
    <Container>
      <Header>
        <div>
          <Title>Lobby</Title>
          <div>Welcome, <strong>{user?.name}</strong> {user?.is_banker && <span style={{color: 'var(--color-gold)'}}>(Banker)</span>}</div>
        </div>
        <button onClick={logout} style={{ color: '#f44336' }}>Logout</button>
      </Header>

      {error && <div style={{ color: 'var(--color-red-debt)', marginBottom: 20 }}>{error}</div>}

      {user?.is_banker && (
        <Card>
          <h2>Banker Controls</h2>
          <p style={{ color: '#aaa', marginBottom: 20 }}>Create a new table instance to start hosting.</p>
          <Button $primary onClick={handleCreate}>Create New Game</Button>
        </Card>
      )}

      <Card>
        <h2>Join Game</h2>
        <form onSubmit={handleJoin} style={{ marginTop: 20 }}>
          <label style={{ display: 'block', marginBottom: 5 }}>Game ID</label>
          <Input 
            type="number" 
            placeholder="e.g. 1" 
            value={joinId} 
            onChange={(e) => setJoinId(e.target.value)} 
            required 
          />
          
          <label style={{ display: 'block', marginBottom: 5 }}>Initial Buy-in (₱)</label>
          <Input 
            type="number" 
            placeholder="Amount" 
            value={buyIn} 
            onChange={(e) => setBuyIn(e.target.value)} 
            required 
            min="0"
          />
          
          <Button type="submit">Join Table</Button>
        </form>
      </Card>
    </Container>
  );
};
