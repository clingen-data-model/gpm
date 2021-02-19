import queryStringFromParams from '../http/query_string_from_params'
import { v4 as uuid4 } from 'uuid';

const { default: axios } = require("axios");

const baseUrl = '/api/applications';

function entityHasUuid(entity) { 
    if (!entity.uuid) {
        throw new Error('Entity must have a uuid to complete the transaction.')
    }
}

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
    const data = await axios.get(baseUrl + '/' + uuid+queryStringFromParams(params))
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
    entityHasUuid(application);

    if (!nextActionData.uuid) {
        nextActionData.uuid = uuid4();
    }

    return await axios.post(`${baseUrl}/${application.uuid}/next-actions`, nextActionData)
        .then(response => {
            return response.data;
        });
}

async function updateEpAttributes(application) {
    entityHasUuid(application);
    
    const {working_name, long_base_name, short_base_name, affiliation_id, cdwg_id} = application;
    const data = {working_name, long_base_name, short_base_name, affiliation_id, cdwg_id};

    return await axios.put(`${baseUrl}/${application.uuid}`, data)
        .then(response => {
            return response.data
        })
}

async function addDocument(application, documentData) {
    entityHasUuid(application);

    if (!documentData.has('uuid')) {
        documentData.append('uuid', uuid4())
    }

    const url = `${baseUrl}/${application.uuid}/documents`;
    return await axios.post(url, documentData)
        .then(response => {
            return response.data;
        });
}

async function markDocumentReviewed(application, document, dateReviewed) {
    entityHasUuid(application);
    entityHasUuid(document);

    const url = `${baseUrl}/${application.uuid}/documents/${document.uuid}/review`
    return await axios.post(url, {date_reviewed: dateReviewed})
        .then(response => {
            return response.data;
        })
}

export { 
    all, 
    find, 
    allVceps, 
    allGceps, 
    initiate, 
    addNextAction, 
    updateEpAttributes, 
    addDocument,
    markDocumentReviewed 
}

export default { 
    all, 
    find, 
    allVceps, 
    allGceps, 
    initiate, 
    addNextAction, 
    updateEpAttributes, 
    addDocument,
    markDocumentReviewed
};