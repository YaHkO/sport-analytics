import { useState, useEffect } from 'react';
import { apiService } from '../services/api';

export function useActivities(filters = {}) {
    const [activities, setActivities] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [pagination, setPagination] = useState({});

    const fetchActivities = async (newFilters = {}) => {
        setLoading(true);
        setError(null);

        try {
            const response = await apiService.getActivities({ ...filters, ...newFilters });
            setActivities(response.data);
            setPagination(response.pagination);
        } catch (err) {
            setError(err.message);
            setActivities([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchActivities();
    }, []);

    const refetch = (newFilters = {}) => {
        fetchActivities(newFilters);
    };

    return { activities, loading, error, pagination, refetch };
}
