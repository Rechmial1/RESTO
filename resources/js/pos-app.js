import { createApp } from "vue";
import PosApp from "./PosApp.vue";
import axios from "axios";

axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.withCredentials = true; // send session cookie
axios.defaults.headers.common["X-CSRF-TOKEN"] = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute("content");

createApp(PosApp).mount("#pos-app");
