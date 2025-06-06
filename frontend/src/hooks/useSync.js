import { useState } from 'react';
import { apiService } from '../services/api';

export function useSync() {
    const [syncing, setSyncing] = useState(false);
    const [syncResult, setSyncResult] = useState(null);

    const sync = async (source = 'strava', limit = null) => {
        setSyncing(true);
        setSyncResult(null);

        try {
            const result = await apiService.syncActivities(source, limit);
            setSyncResult(result);
            return result;
        } catch (error) {
            const errorResult = {
                success: false,
                error: error.message,
                synced_count: 0,
            };
            setSyncResult(errorResult);
            return errorResult;
        } finally {
            setSyncing(false);
        }
    };

    const clearResult = () => {
        setSyncResult(null);
    };

    return { sync, syncing, syncResult, clearResult };
}
