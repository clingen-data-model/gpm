import { GcepApplication, VcepApplication, ScvcepApplication } from "@/domain";

export const getApplicationForGroup = (group) => {
    if (group.is_vcep) {
        return VcepApplication;
    }

    if (group.is_scvcep) {
        return ScvcepApplication;
    }

    return GcepApplication;
};