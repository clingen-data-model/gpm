import {ref} from 'vue'
import commentRepository from '../repositories/comment_repository';

export default (subjectType, subjectId) => {
    const comments = ref([]);

    const findCommentIndex = (comment) => {
        return comments.value.findIndex(c => c.id == comment.id)
    }
    
    const getComments = async () => {
        comments.value = await commentRepository.query({where: {
            subject_type: subjectType,
            subject_id: subjectId
        }})
    }

    const addComment = (comment) => comments.value.push(comment);
    const removeComment = (comment) => comments.value.splice(findCommentIndex(comment), 1);
    const updateComment = (comment) => comments.value[findCommentIndex(comment)] = comment;

    return {
        subject: {
            type: subjectType,
            id: subjectId
        },
        comments,
        getComments,
        addComment,
        removeComment,
        updateComment
    }
}