import API from '../apiService.js';

export const bookShipment = async (formData) => {
    try {
        const response = await API.post('shipment/book', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        return response.data;
    } catch (error) {
        throw error;
    }
};

export const getShipmentById = async (id) => {
    try {
        const response = await API.get(`shipment/${id}`);
        return response.data;
    } catch (error) {
        throw error;
    }
};

export const getShipments = async (params = {}) => {
    try {
        const response = await API.get('shipment/get-list', {
            params
        });
        return response.data;
    } catch (error) {
        throw error;
    }
};

export const getDashboardStats = async (params = {}) => {
    try {
        const response = await API.get('shipment/dashboard-stats', {
            params
        });
        return response.data;
    } catch (error) {
        throw error;
    }
};

export const deleteShipment = async (id) => {
    try {
        const response = await API.delete(`shipment/${id}`);
        return response.data;
    } catch (error) {
        throw error;
    }
};

export default {
    bookShipment,
    getShipmentById,
    getShipments,
    getDashboardStats,
    deleteShipment
};
