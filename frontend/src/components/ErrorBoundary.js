import React from 'react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('ErrorBoundary caught an error:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="error-boundary">
                    <h2>🚨 Oups ! Quelque chose s'est mal passé</h2>
                    <details>
                        <summary>Détails de l'erreur</summary>
                        <pre>{this.state.error?.toString()}</pre>
                    </details>
                    <button
                        onClick={() => this.setState({ hasError: false, error: null })}
                        className="retry-button"
                    >
                        Réessayer
                    </button>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
