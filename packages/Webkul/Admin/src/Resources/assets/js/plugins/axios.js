/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
import axios from "axios";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

/**
 * Explicitly configure the CSRF cookie name and header name so Axios
 * correctly reads the XSRF-TOKEN cookie (URL-decoded) and sends it
 * back as the X-XSRF-TOKEN request header on every POST/PUT/DELETE.
 */
window.axios.defaults.xsrfCookieName = "XSRF-TOKEN";
window.axios.defaults.xsrfHeaderName = "X-XSRF-TOKEN";
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

export default {
    install(app) {
        app.config.globalProperties.$axios = axios;
    },
};

