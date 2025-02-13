import queryStringFromParams from '../http/query_string_from_params'
import { v4 as uuid4 } from 'uuid';

import axios from '@/http/api'

const baseUrl = '/api/applications';

function entityHasUuid(entity) { 
    if (!entity.uuid) {
        throw new Error('Entity must have a uuid to complete the transaction.')
    }
}

async function all(params) {
    const data = await axios.get(baseUrl + queryStringFromParams(params))
        .then(response => response.data.data);
    return data
}

async function find(uuid, params) {
    const data = await axios.get(baseUrl + '/' + uuid+queryStringFromParams(params))
            .then(response => {
                return response.data.data
            })
    return data
}

async function initiate(data) {
    if (!data.uuid) {
        data.uuid = uuid4()
    }
    return await axios.post(baseUrl, data)
        .then(response => response.data)
}

async function approveCurrentStep(application, dateApproved, notifyContacts, notifyClingen){
    entityHasUuid(application)

    const url = `${baseUrl}/${application.uuid}/current-step/approve`
    return await axios.post(url, {date_approved: dateApproved, notify_contacts: notifyContacts, notify_clingen: notifyClingen})
                    .then(response => response.data)
}

async function updateEpAttributes(application) {
    entityHasUuid(application);
    
    const {long_base_name, short_base_name, affiliation_id, cdwg_id} = application;
    const data = {long_base_name, short_base_name, affiliation_id, cdwg_id};

    return await axios.put(`${baseUrl}/${application.uuid}`, data)
        .then(response => response.data)
}

async function addDocument(application, documentData) {
    entityHasUuid(application);

    if (!documentData.has('uuid')) {
        documentData.append('uuid', uuid4())
    }

    const url = `${baseUrl}/${application.uuid}/documents`;
    return await axios.post(url, documentData)
        .then(response => response.data);
}

async function markDocumentReviewed(application, document, dateReviewed) {
    entityHasUuid(application);
    entityHasUuid(document);

    const url = `${baseUrl}/${application.uuid}/documents/${document.uuid}/review`
    return await axios.post(url, {date_reviewed: dateReviewed})
        .then(response => response.data)
}

async function addContact(application, person) {
    entityHasUuid(application);
    entityHasUuid(person)
    const url = `${baseUrl}/${application.uuid}/contacts`
    return await axios.post(url, {person_uuid: person.uuid})
        .then(response => response.data)
}

async function removeContact(application, contact) {
    entityHasUuid(application);
    entityHasUuid(contact);
    
    const url = `${baseUrl}/${application.uuid}/contacts/${contact.uuid}`
    return await axios.delete(url)
                .then(response => response.data);
}

export { 
    all, 
    find, 
    initiate, 
    approveCurrentStep,
    // addNextAction, 
    updateEpAttributes, 
    addDocument,
    markDocumentReviewed,
    addContact,
    removeContact
}

export default { 
    all, 
    find, 
    initiate, 
    approveCurrentStep,
    // addNextAction, 
    updateEpAttributes, 
    addDocument,
    markDocumentReviewed,
    addContact,
    removeContact
};