import React from 'react';

const LoadingSpinner = ({ message = 'Chargement...', size = 'medium' }) => {
    return (
        <div className={`loading-container ${size}`}>
            <div className="spinner"></div>
            <p className="loading-message">{message}</p>
        </div>
    );
};

export default LoadingSpinner;
