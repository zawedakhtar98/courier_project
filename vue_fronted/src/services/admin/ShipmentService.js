import API from "../ApiService";

const saveShipment = async (payload) => {
    try {
        const response = await API.post('admin/shipments/save', payload);
        return response.data;
    } catch (error) {
        throw error;
    }
}

const getAllShipments = async () => {
    try {
        const response = await API.get('admin/shipments/getAll');
        return response.data;
    } catch (error) {
        throw error;
    }
}

export {
    saveShipment,
    getAllShipments
}
