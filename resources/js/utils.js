import configs from '@/configs.json'
import {camelCase} from '@/string_utils'

export const arrayContains = (needle, haystack) => {
    if (!haystack) {
        return false;
    }
    if (typeof needle == 'string') {
        return haystack
            .map(i => i.name)
            .includes(needle);
    }
    if (typeof needle == 'object') {
        return haystack
            .map(i => i.id)
            .includes(needle.id);
    }
    return haystack
        .map(i => i.id)
        .includes(needle);
}

export const featureIsEnabled = feature => {
    return configs.appFeatures[camelCase(feature)]
}

export default {
    arrayContains,
    featureIsEnabled
}
