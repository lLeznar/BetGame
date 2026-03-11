import React, { useEffect, useState } from 'react';
import styled from 'styled-components';
import { formatPeso } from '../utils/formatPeso';

const Bar = styled.div`
  display: flex;
  gap: 12px;
  width: 100%;
  max-width: 400px;
`;

const ActionBtn = styled.button`
  flex: 1;
  padding: 16px;
  border-radius: var(--radius);
  font-weight: 800;
  text-transform: uppercase;
  font-size: 16px;
  color: white;
  
  background: ${props => props.$variant === 'call' ? '#1976d2' : props.$variant === 'fold' ? '#f44336' : '#9e9e9e'};
  box-shadow: 0 4px 0 ${props => props.$variant === 'call' ? '#1565c0' : props.$variant === 'fold' ? '#d32f2f' : '#757575'};
  
  &:active {
    box-shadow: 0 0 0 transparent;
    transform: translateY(4px);
  }

  &:disabled {
    background: #555;
    box-shadow: 0 4px 0 #333;
    opacity: 0.7;
    cursor: not-allowed;
  }
`;

export const ActionBar = ({ disabled, onCall, onCheck, onFold, callAmount }) => {
  const isCheck = callAmount === 0;

  return (
    <Bar>
      <ActionBtn 
        $variant="fold" 
        disabled={disabled} 
        onClick={() => {
          if (window.confirm("Are you sure you want to fold?")) {
            onFold();
          }
        }}
      >
        Fold
      </ActionBtn>
      
      <ActionBtn 
        $variant="call" 
        disabled={disabled} 
        onClick={isCheck ? onCheck : onCall}
      >
        {isCheck ? 'Check ✓' : `Call ${formatPeso(callAmount)}`}
      </ActionBtn>
    </Bar>
  );
};
