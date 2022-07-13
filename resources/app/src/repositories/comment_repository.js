import BaseRepository from './base_repository.js'

export const CommentRepository = (new BaseRepository('/comments'));

export const CommentTypeRepository = (new BaseRepository('/comment-types'));

export default CommentRepository;