import queryStringFromParams from '../http/query_string_from_params'
import { v4 as uuid4 } from 'uuid';

const { default: axios } = require("axios");

const baseUrl = '/api/applications';

function mergeParams(obj1, obj2) {
    const merged = {...obj1, ...obj2 };
    if (obj1.with && obj2.with) {
        merged.with = [...obj1.with, ...obj2.with]
    }
    if (obj1.where && obj2.where) {
        merged.where = {...obj1.where, ...obj2.where }
    }
    return merged;
}

async function all(params) {
    const data = await axios.get(baseUrl + queryStringFromParams(params))
        .then(response => response.data.data);
    return data
}

async function find(uuid, params) {
    const data = await axios.get(baseUrl + '/' + uuid)
            .then(response => {
                // console.log(response.data)
                return response.data
            })
    return data
}

async function allVceps(params) {
    const mergedParams = mergeParams(params, { where: { ep_type_id: 2 } });
    return await all(mergedParams)
}

async function allGceps(params) {
    const mergedParams = mergeParams(params, { where: { ep_type_id: 1 } });
    return await all(mergedParams)
}

async function initiate(data) {
    if (!data.uuid) {
        data.uuid = uuid4()
    }
    return await axios.post(baseUrl, data)
        .then(response => {
            return response.data
        })
}

async function addNextAction(application, nextActionData) {
    if (!application.uuid) {
        throw new Error('application must have a uuid to save a next action.')
    }

    if (!nextActionData.uuid) {
        nextActionData.uuid = uuid4();
    }

    return await axios.post(`${baseUrl}/${application.uuid}/next-actions`, nextActionData)
        .then(response => {
            return response.data;
        });
}

export { all, find, allVceps, allGceps, initiate, addNextAction };