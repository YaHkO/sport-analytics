import React from 'react';

const SportBreakdown = ({ sports, onSportSelect, selectedSport }) => {
    if (!sports || sports.length === 0) {
        return (
            <div className="sport-breakdown">
                <div className="sport-breakdown-empty">
                    <p>🏃‍♂️ Aucune activité trouvée</p>
                </div>
            </div>
        );
    }

    const formatDuration = (hours) => {
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return `${h}h${m > 0 ? ` ${m}m` : ''}`;
    };

    const getPercentage = (value, total) => {
        return total > 0 ? Math.round((value / total) * 100) : 0;
    };

    const totalActivities = sports.reduce((sum, sport) => sum + sport.count, 0);
    const totalDistance = sports.reduce((sum, sport) => sum + sport.distance_km, 0);
    const totalTime = sports.reduce((sum, sport) => sum + sport.time_hours, 0);

    return (
        <div className="sport-breakdown">
            <div className="sport-breakdown-header">
                <button
                    className={`sport-filter-btn ${!selectedSport ? 'active' : ''}`}
                    onClick={() => onSportSelect(null)}
                >
                    🏆 Tous les sports
                </button>
            </div>

            <div className="sport-list">
                {sports.map((sport) => (
                    <div
                        key={sport.sport}
                        className={`sport-item ${selectedSport === sport.sport ? 'selected' : ''}`}
                        onClick={() => onSportSelect(sport.sport === selectedSport ? null : sport.sport)}
                    >
                        <div className="sport-header">
                            <div className="sport-info">
                                <span className="sport-icon">{sport.icon}</span>
                                <span className="sport-name">{sport.sport}</span>
                                {sport.is_endurance && (
                                    <span className="endurance-badge">Endurance</span>
                                )}
                            </div>
                            <div className="sport-count">
                                {sport.count} activité{sport.count > 1 ? 's' : ''}
                            </div>
                        </div>

                        <div className="sport-metrics">
                            <div className="metric-item">
                                <div className="metric-bar">
                                    <div
                                        className="metric-fill distance"
                                        style={{ width: `${getPercentage(sport.distance_km, totalDistance)}%` }}
                                    ></div>
                                </div>
                                <div className="metric-info">
                                    <span className="metric-value">{sport.distance_km} km</span>
                                    <span className="metric-percentage">
                    {getPercentage(sport.distance_km, totalDistance)}% distance
                  </span>
                                </div>
                            </div>

                            <div className="metric-item">
                                <div className="metric-bar">
                                    <div
                                        className="metric-fill time"
                                        style={{ width: `${getPercentage(sport.time_hours, totalTime)}%` }}
                                    ></div>
                                </div>
                                <div className="metric-info">
                                    <span className="metric-value">{formatDuration(sport.time_hours)}</span>
                                    <span className="metric-percentage">
                    {getPercentage(sport.time_hours, totalTime)}% temps
                  </span>
                                </div>
                            </div>

                            <div className="metric-item">
                                <div className="metric-bar">
                                    <div
                                        className="metric-fill count"
                                        style={{ width: `${getPercentage(sport.count, totalActivities)}%` }}
                                    ></div>
                                </div>
                                <div className="metric-info">
                  <span className="metric-percentage">
                    {getPercentage(sport.count, totalActivities)}% activités
                  </span>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="sport-summary">
                <div className="summary-grid">
                    <div className="summary-card">
                        <span className="summary-label">Total activités</span>
                        <span className="summary-value">{totalActivities}</span>
                    </div>
                    <div className="summary-card">
                        <span className="summary-label">Distance totale</span>
                        <span className="summary-value">{totalDistance.toFixed(1)} km</span>
                    </div>
                    <div className="summary-card">
                        <span className="summary-label">Temps total</span>
                        <span className="summary-value">{formatDuration(totalTime)}</span>
                    </div>
                    <div className="summary-card">
                        <span className="summary-label">Sports pratiqués</span>
                        <span className="summary-value">{sports.length}</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SportBreakdown;
