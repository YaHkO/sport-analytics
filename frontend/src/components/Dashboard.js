import React from 'react';
import { useStats } from '../hooks/useStats';
import { useSync } from '../hooks/useSync';
import StatsCards from './StatsCards';
import ActivityChart from './ActivityChart';
import SyncButton from './SyncButton';
import SportBreakdown from './SportBreakdown';

const Dashboard = () => {
    const [period, setPeriod] = React.useState('month');
    const [selectedSport, setSelectedSport] = React.useState(null);

    const { stats, chartData, loading, error, refetch } = useStats(period, selectedSport);
    const { sync, syncing, syncResult, clearResult } = useSync();

    const handleSync = async () => {
        const result = await sync('strava', 10);
        if (result.success) {
            // Recharger les stats après sync
            setTimeout(() => refetch(), 1000);
        }
    };

    if (loading) {
        return (
            <div className="dashboard-loading">
                <div className="spinner"></div>
                <p>Chargement du dashboard...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="dashboard-error">
                <h3>⚠️ Erreur</h3>
                <p>{error}</p>
                <button onClick={() => refetch()} className="retry-btn">
                    Réessayer
                </button>
            </div>
        );
    }

    return (
        <div className="dashboard">
            <div className="dashboard-header">
                <h1>🏃‍♂️ Dashboard Sports Analytics</h1>
                <div className="dashboard-controls">
                    <select
                        value={period}
                        onChange={(e) => setPeriod(e.target.value)}
                        className="period-selector"
                    >
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="3months">3 derniers mois</option>
                        <option value="6months">6 derniers mois</option>
                        <option value="year">Cette année</option>
                    </select>

                    <SyncButton
                        onSync={handleSync}
                        syncing={syncing}
                        syncResult={syncResult}
                        onClearResult={clearResult}
                    />
                </div>
            </div>

            {stats && (
                <>
                    <StatsCards
                        stats={stats.overview}
                        trends={stats.trends}
                        period={period}
                    />

                    <div className="dashboard-content">
                        <div className="chart-section">
                            <h3>📈 Évolution des activités</h3>
                            <ActivityChart
                                data={chartData}
                                period={period}
                            />
                        </div>

                        <div className="breakdown-section">
                            <h3>🏃‍♂️ Répartition par sport</h3>
                            <SportBreakdown
                                sports={stats.sport_breakdown}
                                onSportSelect={setSelectedSport}
                                selectedSport={selectedSport}
                            />
                        </div>
                    </div>
                </>
            )}
        </div>
    );
};

export default Dashboard;
