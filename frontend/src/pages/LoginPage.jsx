import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import styled from 'styled-components';
import { useAuth } from '../hooks/useAuth';

const Container = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
`;

const Card = styled.div`
  background: var(--color-surface);
  padding: 40px;
  border-radius: var(--radius);
  border: 1px solid rgba(255,255,255,0.1);
  width: 100%;
  max-width: 400px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
`;

const Title = styled.h1`
  color: var(--color-gold);
  text-align: center;
  margin-bottom: 30px;
  font-size: 36px;
  text-transform: uppercase;
  letter-spacing: 2px;
`;

const InputGroup = styled.div`
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
`;

const Label = styled.label`
  margin-bottom: 8px;
  font-weight: 600;
  color: #ccc;
`;

const Input = styled.input`
  padding: 15px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.2);
  background: rgba(0,0,0,0.5);
  color: white;
  font-size: 16px;
  font-family: inherit;
  
  &:focus {
    outline: none;
    border-color: var(--color-gold);
  }
`;

const Button = styled.button`
  width: 100%;
  padding: 16px;
  border-radius: 8px;
  font-weight: bold;
  font-size: 18px;
  margin-top: 10px;
  background: var(--color-gold);
  color: black;
  box-shadow: 0 4px 0 #b8972e;
  
  &:active {
    box-shadow: 0 0 0;
  }
`;

const ErrorMsg = styled.div`
  color: var(--color-red-debt);
  margin-bottom: 15px;
  text-align: center;
  font-weight: bold;
`;

const Tabs = styled.div`
  display: flex;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
`;

const Tab = styled.div`
  flex: 1;
  text-align: center;
  padding: 10px;
  cursor: pointer;
  color: ${props => props.$active ? 'var(--color-gold)' : '#888'};
  border-bottom: 2px solid ${props => props.$active ? 'var(--color-gold)' : 'transparent'};
  font-weight: bold;
`;

export const LoginPage = () => {
  const [isLogin, setIsLogin] = useState(true);
  const [name, setName] = useState('');
  const [pin, setPin] = useState('');
  const [isBanker, setIsBanker] = useState(false);
  const [error, setError] = useState('');
  
  const { login, register } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    
    try {
      if (isLogin) {
        await login(name, pin);
      } else {
        await register(name, pin, isBanker);
      }
      navigate('/lobby');
    } catch (err) {
      setError(err.response?.data?.message || 'Authentication failed');
    }
  };

  return (
    <Container>
      <Card>
        <Title>Bet Game</Title>
        
        <Tabs>
          <Tab $active={isLogin} onClick={() => setIsLogin(true)}>Login</Tab>
          <Tab $active={!isLogin} onClick={() => setIsLogin(false)}>Register</Tab>
        </Tabs>

        {error && <ErrorMsg>{error}</ErrorMsg>}

        <form onSubmit={handleSubmit}>
          <InputGroup>
            <Label>Player Name</Label>
            <Input 
              type="text" 
              value={name} 
              onChange={e => setName(e.target.value)} 
              required 
              placeholder="e.g., John"
            />
          </InputGroup>
          
          <InputGroup>
            <Label>4-Digit PIN</Label>
            <Input 
              type="password" 
              value={pin} 
              onChange={e => setPin(e.target.value)} 
              required 
              maxLength="4" 
              pattern="\d{4}"
              placeholder="1234"
            />
          </InputGroup>

          {!isLogin && (
            <InputGroup style={{ flexDirection: 'row', alignItems: 'center' }}>
              <input 
                type="checkbox" 
                id="isBanker" 
                checked={isBanker} 
                onChange={e => setIsBanker(e.target.checked)} 
                style={{ width: 20, height: 20, marginRight: 10 }}
              />
              <Label htmlFor="isBanker" style={{ marginBottom: 0 }}>Create as Banker</Label>
            </InputGroup>
          )}

          <Button type="submit">
            {isLogin ? 'Enter Table' : 'Create Account'}
          </Button>
        </form>
      </Card>
    </Container>
  );
};
