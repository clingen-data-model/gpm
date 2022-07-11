import BaseRepository from './base_repository.js'
import {api} from '@/http'

class CommentTypeRepository extends BaseRepository
{
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

const commentRepository = (new BaseRepository('/api/comments'));
commentRepository.resolve = (id) => {
    return api.post(`/api/comments/${id}/resolved`)
            .then(response => response.data);
}
commentRepository.unresolve = (id) => {
    return api.post(`/api/comments/${id}/unresolved`)
            .then(response => response.data);
}

export {commentRepository};

export const typeRepository = (new CommentTypeRepository('/api/comment-types'));

export default commentRepository;