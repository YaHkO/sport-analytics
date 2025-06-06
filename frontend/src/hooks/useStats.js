import { useState, useEffect } from 'react';
import { apiService } from '../services/api';

export function useStats(period = 'month', sport = null) {
    const [stats, setStats] = useState(null);
    const [chartData, setChartData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchStats = async () => {
        setLoading(true);
        setError(null);

        try {
            const params = { period };
            if (sport) params.sport = sport;

            const [statsResponse, chartResponse] = await Promise.all([
                apiService.getStats(params),
                apiService.getChartData(params)
            ]);

            setStats(statsResponse.data);
            setChartData(chartResponse.data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchStats();
    }, [period, sport]);

    return { stats, chartData, loading, error, refetch: fetchStats };
}
