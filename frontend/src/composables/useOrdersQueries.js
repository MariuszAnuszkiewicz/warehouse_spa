import { inject } from 'vue'
import apiClient from '@/services/apiClient'
import { toast } from 'vue3-toastify'
import { useRouter } from 'vue-router'

export function useOrdersQueries(isLoading = null) {

    const apiDomain = inject('apiDomain')
    const router = useRouter();

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

    const queryRemoveSelected = async ({ selectedIds, orders }) => {
        if (!apiDomain) {
            console.error('apiDomain is not provided')
            return []
        }

        await apiClient.delete(`${apiDomain}/api/order/del`, {
            data: { order_ids: selectedIds.value }
        }).then(() => {
            orders.value = orders?.value.filter(o => o.id !== selectedIds.value.find(ids => ids === o.id))
            selectedIds.value = [];
        });
    };

    const queryUpdateOrder = async (dataForm) => {
        if (!apiDomain) {
            console.error('apiDomain is not provided')
            return []
        }

        try {
            const response = await apiClient.put(`${apiDomain}/api/order/update`,
                dataForm.value
            )

            return response

        } catch (error) {
            console.error(error)
            return [];
        }
    }

    const queryCreateOrder = async (form) => {
        if (!apiDomain) {
            console.error('apiDomain is not provided')
            return []
        }

        try {
            await apiClient.post(apiDomain + '/api/order/create', form.value).then(response => {
                if (response.data) {
                    toast.success('add items to order successfully.');
                    setTimeout(() => {
                        unSelected();
                    }, 5000);
                }
            });
        } catch (error) {
            if (error.status === 401) {
                router.push('/login');
            }
            console.warn(error);
            return [];
        }
    }

    return {
        queryOrders,
        queryOrder,
        queryRemoveSelected,
        queryUpdateOrder,
        queryCreateOrder
    }
}