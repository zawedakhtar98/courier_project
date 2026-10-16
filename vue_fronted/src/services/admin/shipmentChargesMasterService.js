import API from "../ApiService";

const addNewShipCharges = async (formdata) => {
    try {
        const resp = await API.post('admin/shipment-charges-master/add', formdata);
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}

const getShipChargesList = async () => {
    try {
        const resp = await API.get('admin/shipment-charges-master/get-list');
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}
const deleteShipCharge = async (id) => {
    try {
        const resp = await API.delete(`admin/shipment-charges-master/delete/${id}`);
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}

export {
    addNewShipCharges,
    getShipChargesList,
    deleteShipCharge
}