import BaseRepository from './base_repository.js'
import {api} from '@/http'


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

export default commentRepository;