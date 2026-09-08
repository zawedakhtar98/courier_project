import API from "./ApiService";

const login = async (data) => {
    try {
        const resp = await API.post('login', data);

        return resp.data;

    } catch (err) {
        if (err.response.status === 422) {
            return { status: 422, message: "Invalid credentials" };
        }
        return err;
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