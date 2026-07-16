import {api} from '@/http';
import {setImpersonationToken, clearImpersonationToken} from '@/composables/useImpersonationToken';

export const impersonate = (userId) => {
    return api.post(`/api/impersonate/take/${userId}`)
        .then(response => {
            setImpersonationToken(response.data.token);
            // Reload so every store/route re-resolves as the impersonated user.
            window.location.href = '/';
        });
}

export const leaveImpersonation = () => {
    return api.post('/api/impersonate/leave')
        .catch(() => { /* best-effort; the token drop below is what matters */ })
        .finally(() => {
            clearImpersonationToken();
            window.location.href = '/';
        });
}

export const search = (searchString) => {
    return api.get(`/api/impersonate/search?query_string=${searchString}`)
        .then(response => {
            return response.data.data;
        });
}

export default {
    impersonate,
    leaveImpersonation,
    search
}
