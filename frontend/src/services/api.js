// frontend/src/services/api.js - Service API centralisé
const API_BASE_URL = '/api';

class ApiService {
    constructor() {
        this.baseURL = API_BASE_URL;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            },
            ...options,
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Erreur API');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Activities endpoints
    async getActivities(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = `/activities${queryString ? `?${queryString}` : ''}`;
        return this.request(endpoint);
    }

    async getActivity(id) {
        return this.request(`/activities/${id}`);
    }

    async getAvailableSports() {
        return this.request('/activities/sports');
    }

    async syncActivities(source = 'strava', limit = null) {
        return this.request('/activities/sync', {
            method: 'POST',
            body: JSON.stringify({ source, limit }),
        });
    }

    // Stats endpoints
    async getStats(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = `/stats/overview${queryString ? `?${queryString}` : ''}`;
        return this.request(endpoint);
    }

    async getChartData(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = `/stats/chart-data${queryString ? `?${queryString}` : ''}`;
        return this.request(endpoint);
    }
}

export const apiService = new ApiService();
