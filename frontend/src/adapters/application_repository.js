import queryStringFromParams from '../http/query_string_from_params'

const { default: axios } = require("axios");

const baseUrl = '/api/applications';

async function all(params) {
    console.log('before')
    const data = await axios.get(baseUrl + queryStringFromParams(params))
        .then(response => response.data.data);
    console.log('after')
    console.log(data)
    return data
}

export { all };