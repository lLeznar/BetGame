import React, { useEffect, useState } from 'react';
import styled, { keyframes } from 'styled-components';
import { formatPeso } from '../utils/formatPeso';

const slideUp = keyframes`
  0% { transform: translateY(20px); opacity: 0; }
  10% { transform: translateY(0); opacity: 1; }
  90% { transform: translateY(0); opacity: 1; }
  100% { transform: translateY(-20px); opacity: 0; }
`;

const Badge = styled.div`
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(255, 215, 0, 0.9);
  color: black;
  font-weight: 900;
  padding: 8px 16px;
  border-radius: 20px;
  border: 2px solid white;
  box-shadow: 0 4px 15px rgba(0,0,0,0.5);
  z-index: 100;
  animation: ${slideUp} 3s forwards;
  pointer-events: none;
  text-transform: uppercase;
  white-space: nowrap;
`;

export const SideBetBadge = ({ type, payout, onComplete }) => {
  useEffect(() => {
    const timer = setTimeout(() => {
      onComplete();
    }, 3000);
    return () => clearTimeout(timer);
  }, [onComplete]);

  const labels = {
    black_pair: "♠♣ Black Pair",
    one_eye: "👁 One Eye",
    aces: "A Aces Hit"
  };

  return (
    <Badge>
      {labels[type]}! +{formatPeso(payout)}
    </Badge>
  );
};
