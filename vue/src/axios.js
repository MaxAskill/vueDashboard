import axios from "axios";

const axiosClient = axios.create({
  baseURL: `http://192.168.0.7:98/api`,
  headers: {
    'Content-Type': 'application/json',
    'Access-Control-Allow-Origin': '*'
    },
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    withCredentials: false

});

// axiosClient.interceptors.request.use(config => {
//   config.headers.Authorization = `Bearer ${store.state.user.token}`
//   return config;
// })

// axiosClient.interceptors.response.use(response => {
//   return response;
// }, error => {
//   if (error.response.status === 401) {
//     sessionStorage.removeItem('TOKEN')
//     router.push({name: 'Login'})
//   } else if (error.response.status === 404) {
//     router.push({name: 'NotFound'})
//   }
//   return error;
// })

export default axiosClient;