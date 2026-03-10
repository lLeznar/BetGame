import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { GameProvider } from './context/GameContext';
import { useAuth } from './hooks/useAuth';

import { LoginPage } from './pages/LoginPage';
import { LobbyPage } from './pages/LobbyPage';
import { GameTablePage } from './pages/GameTablePage';
import { LedgerPage } from './pages/LedgerPage';

const ProtectedRoute = ({ children }) => {
  const { user, loading } = useAuth();
  
  if (loading) return <div>Loading session...</div>;
  if (!user) return <Navigate to="/" replace />;
  
  return children;
};

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <GameProvider>
          <Routes>
            <Route path="/" element={<LoginPage />} />
            <Route path="/lobby" element={<ProtectedRoute><LobbyPage /></ProtectedRoute>} />
            <Route path="/game/:gameId" element={<ProtectedRoute><GameTablePage /></ProtectedRoute>} />
            <Route path="/game/:gameId/ledger" element={<ProtectedRoute><LedgerPage /></ProtectedRoute>} />
          </Routes>
        </GameProvider>
      </AuthProvider>
    </BrowserRouter>
  );
}

export default App;
