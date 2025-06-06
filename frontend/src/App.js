import React from 'react';
import Dashboard from './components/Dashboard';
import ErrorBoundary from './components/ErrorBoundary';
import './App.css';

function App() {
    return (
        <ErrorBoundary>
            <div className="App">
                <Dashboard />
            </div>
        </ErrorBoundary>
    );
}

export default App;
