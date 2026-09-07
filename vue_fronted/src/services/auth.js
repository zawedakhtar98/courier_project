import API from "./ApiService";

const login = async (data) => {
    try {
        const resp = await API.post('login', data);
        return resp.data;

    } catch (err) {
        console.log("error from login service: ", err);
    }
}

const register = async (data) => {
    const resp = await API.post('register', data);
    return resp.data;
}

const logout = async () => {
    const resp = await API.post('logout');
    return resp.data;
}

export { login, register, logout }