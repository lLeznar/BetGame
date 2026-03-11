import React, { createContext, useState, useEffect } from 'react';
import api from '../services/api';
import { resetEcho } from '../services/echo';

export const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    const loadUser = async () => {
        try {
            // Re-attach token from localStorage if present (survives page refresh)
            const savedToken = localStorage.getItem('auth_token');
            if (savedToken) {
                api.defaults.headers.common['Authorization'] = `Bearer ${savedToken}`;
            }

            const response = await api.get('/auth/me');
            setUser(response.data);
        } catch (error) {
            setUser(null);
            // Clear stale token if re-auth failed
            localStorage.removeItem('auth_token');
            delete api.defaults.headers.common['Authorization'];
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadUser();
    }, []);

    const login = async (name, pin) => {
        // GET /sanctum/csrf-cookie first to establish CSRF protection for SPA
        await api.get('/sanctum/csrf-cookie', { baseURL: '/' });
        
        const response = await api.post('/auth/login', { name, pin });
        setUser(response.data.user);
        
        // Ensure token is attached if not using cookies purely (we are mainly relying on sanctum cookies, but just in case)
        if (response.data.token) {
             localStorage.setItem('auth_token', response.data.token);
             api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        }
        // Reset Echo so it rebuilds with the fresh token
        resetEcho();
        return response.data;
    };

    const register = async (name, pin, is_banker = false) => {
        const response = await api.post('/auth/register', { name, pin, is_banker });
        setUser(response.data.user);
        
        if (response.data.token) {
             localStorage.setItem('auth_token', response.data.token);
             api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        }
        // Reset Echo so it rebuilds with the fresh token
        resetEcho();
        return response.data;
    };

    const logout = async () => {
        try {
            await api.post('/auth/logout');
        } finally {
            setUser(null);
            localStorage.removeItem('auth_token');
            delete api.defaults.headers.common['Authorization'];
            resetEcho();
        }
    };

    return (
        <AuthContext.Provider value={{ user, loading, login, register, logout, setUser }}>
            {children}
        </AuthContext.Provider>
    );
};
