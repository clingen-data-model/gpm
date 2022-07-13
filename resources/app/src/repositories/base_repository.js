import { api } from '@/http'

export default class BaseRepository {
    constructor (baseUrl, options = {}) {
        this.baseUrl = baseUrl
        this.options = options
    }

    query () {
        return api.get(this.baseUrl).then(response =>  this.transformToEntity(response.data))
    }

    find (id) {
        return api.get(`${this.baseUrl}/${id}`).then(response => this.transformToEntity(response.data))
    }

     save (data) {
        return api.post(this.baseUrl, data).then(response => this.transformToEntity(response.data))
    }
    
     update (data) {
        return api.put(`${this.baseUrl}/${data.id}`, data).then(response => this.transformToEntity(response.data))
    }
    
     destroy (item) {
        return api.delete(`${this.baseUrl}/${data.id}`)
    }

    transformToEntity(item) {
        if (!this.options.entityClass) {
            return item;
        }

        if (Array.isArray(item)) {
            return item.map(i => new this.options.entityClass(i))
        }
        return item;
    }
}