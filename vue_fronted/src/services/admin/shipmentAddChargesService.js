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

const bulkAddShipAdditionalCharges = async (data) => {
    try {
        const resp = await API.post('admin/shipment-additional-charges/bulk-add', data);
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
        return { status: 'error', message: err.message };
    }
}

const bulkUploadShipAdditionalCharges = async (formData) => {
    try {
        const resp = await API.post('admin/shipment-additional-charges/bulk-upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
        return { status: 'error', message: err.message };
    }
}

const updateShipAdditionalCharge = async (id, data) => {
    try {
        const resp = await API.put(`admin/shipment-additional-charges/update/${id}`, data);
        return resp.data;
    }
    catch (err) {
        if (err.response?.data) {
            return err.response.data;
        }
        return { status: 'error', message: err.message };
    }
}

export {
    addNewShipAdditionalCharge,
    bulkAddShipAdditionalCharges,
    bulkUploadShipAdditionalCharges,
    updateShipAdditionalCharge,
    getShipAdditionalChargesList,
    deleteShipAdditionalCharge
}