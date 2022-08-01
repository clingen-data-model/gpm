import {ref, computed} from 'vue'
import commentRepository from '../repositories/comment_repository';

const types = {
    internal: 1,
    suggestion: 2,
    requirement: 3
};

export default (subjectType, subjectId) => {
    const comments = ref([]);

    const openComments = computed(() => comments.value.filter(c => !c.is_resolved))
    const openRequirements = computed(() => openComments.value.filter(c =>  c.comment_type_id == types.requirement));
    const openSuggestions = computed(() => openComments.value.filter(c =>  c.comment_type_id == types.suggestion));
    const commentsForEp = computed(() => [...openRequirements.value, ...openSuggestions.value])
    const openInternal = computed(() => openComments.value.filter(c => c.comment_type_id == types.internal));

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
        openComments,
        openRequirements,
        openSuggestions,
        openInternal,
        getComments,
        addComment,
        removeComment,
        updateComment,
        commentsForEp
    }
}
