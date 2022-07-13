import {ref} from 'vue';
import {isValidationError} from '@/http'

class BaseEntityForm {
    constructor(fields, repository) {
        this.fields = fields;
        this.repo = repository
        
        this.currentItem = ref({})
        this.originalItem = ref({})
        this.errors = ref({})
    }

     async find (id) {
        const data =  await this.repo.find(id)
            .then(data => {
                this.currentItem.value = data
                this.originalItem.value = data
                return data
            });
    }

     async save (data) {
        this.clearErrors()
        try {
            const newItem = await this.repo.save(data)
            this.clearCurrentItem()
            return newItem;
        } catch (e) {
            if (isValidationError(e)) {
                this.errors.value = e.response.data.errors
            }
            throw e
        }
    }
    
     async update (data) {
        this.clearErrors()
        try {
            this.currentItem.value = this.repo.update(data)
        } catch (e) {
            if (isValidationError(e)) {
                this.errors.value = e.response.data.errors
            }
            throw e
        }
    }
    
     async destroy (item) {
        console.log(`delete assay_class with id: ${item.id}`);
    }
    
    cancel () {
        this.clearErrors()
        if (!this.currentItem.value.id) {
            this.clearCurrentItem()
            return;
        }
    }
    
    clearCurrentItem () {
        this.currentItem.value = {}
    }
    
    clearErrors () {
        this.errors.value = {}
    }
}

export default BaseEntityForm