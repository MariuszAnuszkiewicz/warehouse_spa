import { inject } from 'vue'
import apiClient from '@/services/apiClient'

export function useLocationsQueries(isLoading = null) {

    const apiDomain = inject('apiDomain')

    const queryLocations = async () => {
        if (!apiDomain) {
            console.error('apiDomain is not provided')
            return []
        }

        try {
            const response = await apiClient.get(apiDomain + '/api/locations')
            return JSON.parse(response.data.locations || '[]')
        } catch (error) {
            console.warn(error);
            return [];
        } finally {
            isLoading.value = false;
        }
    }

    return {
        queryLocations
    }
}