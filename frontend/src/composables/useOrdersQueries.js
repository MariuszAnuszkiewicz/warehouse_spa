import { inject } from 'vue'
import apiClient from '@/services/apiClient'

export function useOrdersQueries(isLoading) {

    if (!isLoading) console.error('isLoading parameter is not exists')

    const apiDomain = inject('apiDomain')

    const queryOrders = async () => {
        if (!apiDomain) {
            console.error('apiDomain is not provided')
            return []
        }

        try {
            const response = await apiClient.get(apiDomain + '/api/orders')
            return JSON.parse(response.data.orders || '[]')
        } catch (error) {
            console.warn(error);
            return [];
        } finally {
            isLoading.value = false;
        }
    }

    const queryOrder = async (id) => {
        if (!apiDomain) {
            console.error('apiDomain is not provided')
            return []
        }

        try {
            const response = await apiClient.get(apiDomain + `/api/order/${id}`)
            return JSON.parse(response.data.order || '[]')
        } catch (error) {
            console.warn(error);
            return [];
        } finally {
            isLoading.value = false;
        }
    }

    const queryStocks = async () => {
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

    const queryLocations = async () => {
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

    const queryRemoveSelected = async ({ selectedIds, orders }) => {
        await apiClient.delete(`${apiDomain}/api/order/del`, {
            data: { order_ids: selectedIds.value }
        }).then(() => {
            orders.value = orders?.value.filter(o => o.id !== selectedIds.value.find(ids => ids === o.id))
            selectedIds.value = [];
        });
    };

    return {
        queryOrders,
        queryOrder,
        queryStocks,
        queryLocations,
        queryRemoveSelected
    }
}