import React from 'react';

const SyncButton = ({ onSync, syncing, syncResult, onClearResult }) => {
    const getButtonText = () => {
        if (syncing) return '🔄 Synchronisation...';
        if (syncResult?.success) return '✅ Synchronisé';
        if (syncResult?.success === false) return '❌ Erreur';
        return '🔄 Synchroniser Strava';
    };

    const getButtonClass = () => {
        let baseClass = 'sync-button';
        if (syncing) baseClass += ' syncing';
        if (syncResult?.success) baseClass += ' success';
        if (syncResult?.success === false) baseClass += ' error';
        return baseClass;
    };

    React.useEffect(() => {
        if (syncResult) {
            const timer = setTimeout(() => {
                onClearResult();
            }, 3000);
            return () => clearTimeout(timer);
        }
    }, [syncResult, onClearResult]);

    return (
        <div className="sync-container">
            <button
                onClick={onSync}
                disabled={syncing}
                className={getButtonClass()}
            >
                {getButtonText()}
            </button>

            {syncResult && (
                <div className={`sync-result ${syncResult.success ? 'success' : 'error'}`}>
                    {syncResult.success ? (
                        <span>✅ {syncResult.synced_count} activités synchronisées</span>
                    ) : (
                        <span>❌ {syncResult.error}</span>
                    )}
                </div>
            )}
        </div>
    );
};

export default SyncButton;
