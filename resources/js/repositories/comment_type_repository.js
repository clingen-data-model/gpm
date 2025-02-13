import BaseRepository from './base_repository.js'

class CommentTypeRepository extends BaseRepository
{
    constructor (baseUrl, options = {}) {
        super(baseUrl, options);
        this.cachedList = null;
    }

    query (params) {
        if (!this.cachedList) {
            const results = super.query(params)
            this.cachedList = results;
        }

        return this.cachedList
    }
    
    // eslint-disable-next-line
    find (id) {
        throw Error('CommentTypeRepository.find not implemented');
    }

    // eslint-disable-next-line
    save (data) {
        throw Error('CommentTypeRepository.save not implemented');
    }

    // eslint-disable-next-line
    update (id) {
        throw Error('CommentTypeRepository.update not implemented');
    }

    // eslint-disable-next-line
    destroy (id) {
        throw Error('CommentTypeRepository.destroy not implemented');
    }
}

export const typeRepository = (new CommentTypeRepository('/api/comment-types'));
export default typeRepository