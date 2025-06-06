import React from 'react';

const StatsCards = ({ stats, trends, period }) => {
    const formatDuration = (hours) => {
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return `${h}h ${m}m`;
    };

    const getTrendIcon = (value) => {
        if (value > 0) return '📈';
        if (value < 0) return '📉';
        return '➡️';
    };

    const getTrendClass = (value) => {
        if (value > 0) return 'trend-up';
        if (value < 0) return 'trend-down';
        return 'trend-neutral';
    };

    return (
        <div className="stats-grid">
            <div className="stats-card">
                <div className="stats-header">
                    <span className="stats-icon">📊</span>
                    <h3>Activités</h3>
                </div>
                <div className="stats-value">{stats.total_activities}</div>
                {trends && (
                    <div className={`stats-trend ${getTrendClass(trends.activities)}`}>
                        {getTrendIcon(trends.activities)} {Math.abs(trends.activities)}%
                    </div>
                )}
            </div>

            <div className="stats-card">
                <div className="stats-header">
                    <span className="stats-icon">🏃‍♂️</span>
                    <h3>Distance</h3>
                </div>
                <div className="stats-value">{stats.total_distance_km} km</div>
                {trends && (
                    <div className={`stats-trend ${getTrendClass(trends.distance)}`}>
                        {getTrendIcon(trends.distance)} {Math.abs(trends.distance)}%
                    </div>
                )}
            </div>

            <div className="stats-card">
                <div className="stats-header">
                    <span className="stats-icon">⏱️</span>
                    <h3>Temps</h3>
                </div>
                <div className="stats-value">{formatDuration(stats.total_time_hours)}</div>
                {trends && (
                    <div className={`stats-trend ${getTrendClass(trends.time)}`}>
                        {getTrendIcon(trends.time)} {Math.abs(trends.time)}%
                    </div>
                )}
            </div>

            <div className="stats-card">
                <div className="stats-header">
                    <span className="stats-icon">⚡</span>
                    <h3>Vitesse moy.</h3>
                </div>
                <div className="stats-value">
                    {stats.average_speed_kmh > 0 ? `${stats.average_speed_kmh} km/h` : 'N/A'}
                </div>
            </div>

            <div className="stats-card">
                <div className="stats-header">
                    <span className="stats-icon">⛰️</span>
                    <h3>Dénivelé</h3>
                </div>
                <div className="stats-value">{stats.total_elevation_m} m</div>
            </div>

            <div className="stats-card">
                <div className="stats-header">
                    <span className="stats-icon">❤️</span>
                    <h3>Avec FC</h3>
                </div>
                <div className="stats-value">
                    {stats.with_hr_data}/{stats.total_activities}
                </div>
                <div className="stats-subtitle">
                    {stats.total_activities > 0
                        ? Math.round((stats.with_hr_data / stats.total_activities) * 100)
                        : 0}%
                </div>
            </div>
        </div>
    );
};

export default StatsCards;
