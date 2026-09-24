import {ref, computed} from 'vue'
import commentRepository from '../repositories/comment_repository';
import {api} from '@/http';

const types = {
    internal: 1,
    suggestion: 2,
    requirement: 3
};

export default (subjectType, subjectId, groupUuid = null, rootGroupId = null) => {
    const comments = ref([]);
    const summary = ref(null);
    const summaryLoading = ref(false);
    const summaryError = ref('');
    let summaryRequest = 0;
    let commentRequest = 0;
    const getSummary = async () => {
        if (!groupUuid) return;
        const request = ++summaryRequest;
        summaryLoading.value = true;
        summaryError.value = '';
        try {
            const response = await api.get(`/api/groups/${groupUuid}/application/review-summary`);
            if (request === summaryRequest) summary.value = response.data;
        } catch {
            if (request === summaryRequest) summaryError.value = 'Unable to load Application Review Summary. Refresh to retry.';
        } finally {
            if (request === summaryRequest) summaryLoading.value = false;
        }
    };
    const dispose = () => { summaryRequest++; commentRequest++; };


    const openComments = computed(() => comments.value.filter(c => !c.is_resolved))
    const openRequirements = computed(() => openComments.value.filter(c =>  c.comment_type_id === types.requirement));
    const openSuggestions = computed(() => openComments.value.filter(c =>  c.comment_type_id === types.suggestion));
    const commentsForEp = computed(() => [...openRequirements.value, ...openSuggestions.value])
    const openInternal = computed(() => openComments.value.filter(c => c.comment_type_id === types.internal));

    const findCommentIndex = (comment) => {
        return comments.value.findIndex(c => c.id === comment.id)
    }

    const getComments = async () => {
        const request = ++commentRequest;
        const result = await commentRepository.query({group_id: rootGroupId, where: {
            subject_type: subjectType,
            subject_id: subjectId
        }})
        if (request === commentRequest) comments.value = result;
    }

    const addComment = (comment) => { comments.value.push(comment); return getSummary(); };
    const removeComment = (comment) => { const index = findCommentIndex(comment); if (index >= 0) comments.value.splice(index, 1); return getSummary(); };
    const updateComment = (comment) => { const index = findCommentIndex(comment); if (index >= 0) comments.value[index] = comment; return getSummary(); };

    return {
        subject: {
            type: subjectType,
            id: subjectId
        },
        comments,
        rootGroupId: rootGroupId ?? subjectId,
        summary, summaryLoading, summaryError, getSummary, dispose,
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
