import React, { useEffect, useState } from 'react';
import styled, { keyframes } from 'styled-components';
import { formatPeso } from '../utils/formatPeso';

const pop = keyframes`
  0% { transform: scale(1); }
  50% { transform: scale(1.1); color: var(--color-gold); }
  100% { transform: scale(1); }
`;

const Container = styled.div`
  background: rgba(0, 0, 0, 0.6);
  border: 2px solid var(--color-gold);
  border-radius: 20px;
  padding: 15px 30px;
  text-align: center;
  box-shadow: 0 0 20px rgba(0,0,0,0.5);
  display: inline-block;
`;

const Label = styled.div`
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: #bbb;
  margin-bottom: 5px;
`;

const Amount = styled.div`
  font-family: var(--font-mono);
  font-size: 32px;
  font-weight: bold;
  color: white;
  
  &.changed {
    animation: ${pop} 0.5s ease;
  }
`;

export const PotDisplay = ({ amount }) => {
  const [animate, setAnimate] = useState(false);

  useEffect(() => {
    setAnimate(true);
    const timer = setTimeout(() => setAnimate(false), 500);
    return () => clearTimeout(timer);
  }, [amount]);

  return (
    <Container>
      <Label>Current Pot</Label>
      <Amount className={animate ? 'changed' : ''}>
        {formatPeso(amount || 0)}
      </Amount>
    </Container>
  );
};
