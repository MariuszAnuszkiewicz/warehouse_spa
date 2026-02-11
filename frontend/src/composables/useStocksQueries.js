import { inject } from 'vue'
import apiClient from '@/services/apiClient'

export function useStocksQueries(isLoading = null) {

    const apiDomain = inject('apiDomain')

    const queryStocks = async () => {
        if (!apiDomain) {
            console.error('apiDomain is not provided')
            return []
        }

        try {
            const response = await apiClient.get(apiDomain + '/api/stocks')
            return JSON.parse(response.data.stocks || '[]')
        } catch (error) {
            console.warn(error);
            return [];
        } finally {
            isLoading.value = false;
        }
    }

    return {
        queryStocks
    }
}