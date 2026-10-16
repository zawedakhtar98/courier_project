import API from "../ApiService";

const addNewShipAdditionalCharge = async (formdata) => {
    try {
        const resp = await API.post('admin/shipment-additional-charges/add', formdata);
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}

const getShipAdditionalChargesList = async () => {
    try {
        const resp = await API.get('admin/shipment-additional-charges/get-list');
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}

const deleteShipAdditionalCharge = async (id) => {
    try {
        const resp = await API.delete(`admin/shipment-additional-charges/delete/${id}`);
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
    }
}

export {
    addNewShipAdditionalCharge,
    getShipAdditionalChargesList,
    deleteShipAdditionalCharge
}