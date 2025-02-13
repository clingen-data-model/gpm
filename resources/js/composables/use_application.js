import { GcepApplication, VcepApplication } from "@/domain";

export const getApplicationForGroup = (group) =>
    group.is_vcep_or_scvcep ? VcepApplication : GcepApplication;
