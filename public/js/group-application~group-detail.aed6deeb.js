(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["group-application~group-detail"],{3926:function(e,t,r){"use strict";var n=r("7a23"),o=Object(n["createElementVNode"])("p",null,[Object(n["createTextVNode"])(" Curated variants and genes are expected to be approved and posted for the community as soon as possible as described in Section 2.4 "),Object(n["createElementVNode"])("a",{class:"link",target:"vcep-protocol",href:"https://clinicalgenome.org/site/assets/files/3635/variant_curation_expert_panel_vcep_protocol_version_9-2.pdf"}," VCEP Protocol "),Object(n["createTextVNode"])(". Note that upon approval, a VCEP must finalize their set of variants for upload to the ClinGen Evidence Repository within 30 days. ")],-1),a={class:"my-4"},i=Object(n["createElementVNode"])("p",null,[Object(n["createTextVNode"])(" Please review the "),Object(n["createElementVNode"])("a",{class:"link",target:"pub-policy",href:"https://clinicalgenome.org/site/assets/files/6737/clingen_publication_policy_june2021_final.pdf"}," ClinGen Publication Policy "),Object(n["createTextVNode"])(" and refer to guidance on submissions to a preprint server (e.g. bioRxiv or medRxiv). ")],-1);function c(e,t,r,c,l,s){var d=this,u=Object(n["resolveComponent"])("checkbox"),p=Object(n["resolveComponent"])("input-row");return Object(n["openBlock"])(),Object(n["createElementBlock"])("div",null,[o,Object(n["createElementVNode"])("p",a,[Object(n["createVNode"])(p,{label:"",vertical:!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(u,{disabled:r.disabled,modelValue:d.group.expert_panel.nhgriSigned,"onUpdate:modelValue":t[0]||(t[0]=function(e){return d.group.expert_panel.nhgriSigned=e}),id:"nhgri-checkbox",label:"I understand that once a variant is approved in the VCI it will become publicly available in the Evidence Repository. They should not be held for publication."},null,8,["disabled","modelValue"])]})),_:1})]),i])}var l=r("1da1"),s=(r("96cf"),r("f96b")),d=r("033d"),u={name:"NHGRIDataAvailability",props:{disabled:{type:Boolean,required:!1,default:!1}},data:function(){return{attestation:null,errors:{}}},computed:{group:{get:function(){return this.$store.getters["groups/currentItemOrNew"]},set:function(e){this.$store.commit("groups/addItem",e)}}},methods:{save:function(){var e=this;return Object(l["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:if(!e.attestation){t.next=9;break}return t.prev=1,t.next=4,s["a"].post("/api/groups/".concat(e.group.uuid,"/application/attestations/nhgri"),{attestation:e.attestation});case 4:t.next=9;break;case 6:t.prev=6,t.t0=t["catch"](1),Object(d["a"])(t.t0)&&(e.errors=t.t0.response.data.errors);case 9:case"end":return t.stop()}}),t,null,[[1,6]])})))()}}};u.render=c;t["a"]=u},"4d1a":function(e,t,r){"use strict";var n=r("7a23"),o={class:"flex justify-between items-center"},a=Object(n["createElementVNode"])("h4",null,"Description of Expertise",-1),i={class:"mt-4"},c={key:1},l={key:1,class:"well"};function s(e,t,r,s,d,u){var p=Object(n["resolveComponent"])("edit-icon-button"),b=Object(n["resolveComponent"])("input-row"),m=Object(n["resolveComponent"])("markdown-block");return Object(n["openBlock"])(),Object(n["createElementBlock"])("div",null,[Object(n["createElementVNode"])("header",o,[a,e.hasAnyPermission(["groups-manage"],["edit-info",u.group])&&!r.editing?(Object(n["openBlock"])(),Object(n["createBlock"])(p,{key:0,onClick:t[0]||(t[0]=function(t){return e.$emit("update:editing",!0)})})):Object(n["createCommentVNode"])("",!0)]),Object(n["createElementVNode"])("div",i,[Object(n["createVNode"])(n["Transition"],{name:"fade",mode:"out-in"},{default:Object(n["withCtx"])((function(){return[r.editing?(Object(n["openBlock"])(),Object(n["createBlock"])(b,{key:0,vertical:!0,label:"Describe the expertise of VCEP members who regularly use the ACMG/AMP guidelines to classify variants and/or review variants during clinical laboratory case sign-out.",errors:r.errors.membership_description},{default:Object(n["withCtx"])((function(){return[Object(n["withDirectives"])(Object(n["createElementVNode"])("textarea",{class:"w-full",rows:"10","onUpdate:modelValue":t[1]||(t[1]=function(e){return u.group.expert_panel.membership_description=e})},null,512),[[n["vModelText"],u.group.expert_panel.membership_description]])]})),_:1},8,["errors"])):(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",c,[u.group.expert_panel.membership_description?(Object(n["openBlock"])(),Object(n["createBlock"])(m,{key:0,markdown:u.group.expert_panel.membership_description},null,8,["markdown"])):(Object(n["openBlock"])(),Object(n["createElementBlock"])("p",l," A description of expertise has not yet been provided. "))]))]})),_:1})])])}var d={name:"MembershipDescriptionForm",props:{editing:{type:Boolean,required:!1,default:!1},errors:{type:Object,required:!1,default:function(){return{}}}},emits:["update:editing","update:group","saved","canceled"],computed:{group:{get:function(){return this.$store.getters["groups/currentItemOrNew"]},set:function(e){this.$store.commit("groups/addItem",e)}}}};d.render=s;t["a"]=d},"8fec":function(e,t,r){"use strict";var n=r("7a23"),o=Object(n["createElementVNode"])("p",null," The Gene Curation Expert Panel (GCEP) leaders(s) will complete the checkbox attestations document below on behalf of the GCEP. ",-1),a={class:"ml-4 mt-2"},i=Object(n["createTextVNode"])(" This GCEP will utilize the ClinGen Gene Tracker for documentation of all precuration information, consistent with the current Lumping and Splitting working group guidance, for gene-disease relationships. "),c=Object(n["createTextVNode"])(" All curations completed by this group will be made publicly available through the ClinGen website immediately upon completion. "),l=Object(n["createTextVNode"])(" VCEPs are expected to re-review any LB classifications when new evidence is available or when requested by the public via the ClinGen website. "),s=Object(n["createTextVNode"])(" The "),d=Object(n["createElementVNode"])("a",{href:"https://clinicalgenome.org/site/assets/files/3752/clingen_publication_policy_apr2019.pdf",target:"pub-policy"},"ClinGen publication policy",-1),u=Object(n["createTextVNode"])(" has been reviewed and a manuscript concept sheet will be submitted to the NHGRI and ClinGen Steering Committee before the group prepares a publication for submission. "),p=Object(n["createTextVNode"])(" Draft manuscripts will be submitted to the ClinGen Gene Curation WG for review prior to submission. Email: "),b=Object(n["createElementVNode"])("a",{href:"mailto:genecuration@clinicalgenome.org"},"mailto:genecuration@clinicalgenome.org",-1),m=Object(n["createTextVNode"])(" The ClinGen Gene-Disease Validity Recuration process has been reviewed, link found "),f=Object(n["createElementVNode"])("a",{href:"https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf"},"here",-1),h=Object(n["createTextVNode"])(". "),g=Object(n["createElementVNode"])("p",null,[Object(n["createTextVNode"])(" Biocurators are expected to become familiar with the ClinGen training materials located on "),Object(n["createElementVNode"])("a",{href:"https://clinicalgenome.org/docs/?doc-type=training-materials#list_documentation_table",target:"clinicalgenome"},"clinicalgenome.org"),Object(n["createTextVNode"])(" website. Biocurators are requested to join the mailing list for ClinGen Biocurator Working Group WG, and expected to attend those calls that focus on gene curation SOP and/or framework updates. ")],-1),j={class:"ml-4 mt-2"},O=Object(n["createTextVNode"])(" Biocurators have received all appropriate training. "),_=Object(n["createTextVNode"])(" Biocurators are trained on the use of the Gene Curation Interface (GCI). "),V=Object(n["createTextVNode"])(" Biocurators have joined the Biocurator WG mailing list. "),x=Object(n["createElementVNode"])("br",null,null,-1),v=Object(n["createTextVNode"])("The calls occur on the 2nd and 4th Thursdays from 12-1pm. ");function w(e,t,r,w,N,C){var y=Object(n["resolveComponent"])("checkbox"),k=Object(n["resolveComponent"])("input-row");return Object(n["openBlock"])(),Object(n["createElementBlock"])("div",null,[o,Object(n["createElementVNode"])("ul",a,[Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.utilize_gt,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.utilize_gt,"onUpdate:modelValue":t[0]||(t[0]=function(e){return C.group.expert_panel.utilize_gt=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[i]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.utilize_gci,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.utilize_gci,"onUpdate:modelValue":t[1]||(t[1]=function(e){return C.group.expert_panel.utilize_gci=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[c]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.curations_publicly_available,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.curations_publicly_available,"onUpdate:modelValue":t[2]||(t[2]=function(e){return C.group.expert_panel.curations_publicly_available=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[l]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.pub_policy_reviewed,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.pub_policy_reviewed,"onUpdate:modelValue":t[3]||(t[3]=function(e){return C.group.expert_panel.pub_policy_reviewed=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[s,d,u]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.draft_manuscripts,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.draft_manuscripts,"onUpdate:modelValue":t[4]||(t[4]=function(e){return C.group.expert_panel.draft_manuscripts=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[p,b]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.recuration_process_review,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.recuration_process_review,"onUpdate:modelValue":t[5]||(t[5]=function(e){return C.group.expert_panel.recuration_process_review=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[m,f,h]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])])]),g,Object(n["createElementVNode"])("ul",j,[Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.biocurator_training,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.biocurator_training,"onUpdate:modelValue":t[6]||(t[6]=function(e){return C.group.expert_panel.biocurator_training=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[O]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(y,{modelValue:N.gci_training,"onUpdate:modelValue":t[7]||(t[7]=function(e){return N.gci_training=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[_]})),_:1},8,["modelValue","disabled"]),Object(n["withDirectives"])(Object(n["createVNode"])(k,{modelValue:C.group.expert_panel.gci_training_date,"onUpdate:modelValue":t[8]||(t[8]=function(e){return C.group.expert_panel.gci_training_date=e}),errors:C.gciTrainingErrors,label:"Biocurators are trained on the use of the Gene Curation Interface (GCI) on.",vertical:"",type:"date"},null,8,["modelValue","errors"]),[[n["vShow"],N.gci_training]])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(k,{errors:N.errors.biocurator_mailing_list,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(y,{modelValue:C.group.expert_panel.biocurator_mailing_list,"onUpdate:modelValue":t[9]||(t[9]=function(e){return C.group.expert_panel.biocurator_mailing_list=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[V,x,v]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])])])])}var N=r("1da1"),C=r("2909"),y=(r("96cf"),r("99af"),r("db04")),k={name:"AttestationGcep",props:{disabled:{type:Boolean,required:!1,default:!1}},data:function(){return{errors:{},gci_training:!1}},computed:{group:{get:function(){return this.$store.getters["groups/currentItemOrNew"]},set:function(e){this.$store.commit("groups/addItem",e)}},gciTrainingErrors:function(){var e=this.errors.gci_training||[],t=this.errors.gci_training_date||[];return[].concat(Object(C["a"])(e),Object(C["a"])(t))},gci_training_comp:function(){return Boolean(this.group.expert_panel.gci_training_date)}},watch:{group:{immediate:!0,handler:function(e){this.gci_training=Boolean(e.expert_panel.gci_training_date)}}},methods:{save:function(){var e=this;return Object(N["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,e.errors={},t.next=4,y["a"].post("/api/groups/".concat(e.group.uuid,"/application/attestations/gcep"),e.group.expert_panel.attributes);case 4:t.next=9;break;case 6:t.prev=6,t.t0=t["catch"](0),Object(y["c"])(t.t0)&&(e.errors=t.t0.response.data.errors);case 9:case"end":return t.stop()}}),t,null,[[0,6]])})))()}}};k.render=w;t["a"]=k},a266:function(e,t,r){"use strict";var n=r("7a23"),o=Object(n["createElementVNode"])("p",null,[Object(n["createTextVNode"])(" Expert Panels are expected to keep their variant interpretations up-to-date and to expedite the re-review of variants that have a conflicting assertion submitted to ClinVar after the Expert Panel submission. Please check all 3 boxes below to attest that the VCEP will follow the ClinGen-approved schedule described below "),Object(n["createElementVNode"])("strong",null,[Object(n["createElementVNode"])("em",null,"or")]),Object(n["createTextVNode"])(" describe other plans at the bottom of the section. ")],-1),a={class:"ml-4 mt-2"},i=Object(n["createTextVNode"])(" VCEPs are expected to reassess any newly submitted conflicting assertion in ClinVar from a one star submitter or above and attempt to resolve or address the conflict within 6 months of being notified about the conflict from ClinGen. Please reach out to the submitter if you need additional information about the conflicting assertion. "),c=Object(n["createTextVNode"])(" VCEPs are expected to re-review all LP and VUS classifications made by the EP at least every 2 years to see if new evidence has emerged to re-classify the variants "),l=Object(n["createTextVNode"])(" VCEPs are expected to re-review any LB classifications when new evidence is available or when requested by the public via the ClinGen website. "),s=Object(n["createTextVNode"])(" Plans differ from the expectations above. "),d={key:0,class:"ml-4"},u=Object(n["createElementVNode"])("label",{for:"reanalysis-other-textarea"},"Explain differences:",-1),p=["disabled"];function b(e,t,r,b,m,f){var h=Object(n["resolveComponent"])("checkbox"),g=Object(n["resolveComponent"])("input-row");return Object(n["openBlock"])(),Object(n["createElementBlock"])("div",null,[o,Object(n["createElementVNode"])("ul",a,[Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(g,{errors:m.errors.reanalysis_conflicting,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(h,{modelValue:f.group.expert_panel.reanalysis_conflicting,"onUpdate:modelValue":t[0]||(t[0]=function(e){return f.group.expert_panel.reanalysis_conflicting=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[i]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(g,{errors:m.errors.reanalysis_review_lp,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(h,{modelValue:f.group.expert_panel.reanalysis_review_lp,"onUpdate:modelValue":t[1]||(t[1]=function(e){return f.group.expert_panel.reanalysis_review_lp=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[c]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(g,{errors:m.errors.reanalysis_review_lb,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(h,{modelValue:f.group.expert_panel.reanalysis_review_lb,"onUpdate:modelValue":t[2]||(t[2]=function(e){return f.group.expert_panel.reanalysis_review_lb=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[l]})),_:1},8,["modelValue","disabled"])]})),_:1},8,["errors"])]),Object(n["createElementVNode"])("li",null,[Object(n["createVNode"])(g,{errors:m.errors.reanalysis_other,"hide-label":!0},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(h,{modelValue:m.otherCheckbox,"onUpdate:modelValue":t[3]||(t[3]=function(e){return m.otherCheckbox=e}),disabled:r.disabled},{default:Object(n["withCtx"])((function(){return[s]})),_:1},8,["modelValue","disabled"]),Object(n["createVNode"])(n["Transition"],{name:"slide-fade-down"},{default:Object(n["withCtx"])((function(){return[m.otherCheckbox?(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",d,[u,Object(n["withDirectives"])(Object(n["createElementVNode"])("textarea",{"onUpdate:modelValue":t[4]||(t[4]=function(e){return f.group.expert_panel.reanalysis_other=e}),class:"w-full",id:"reanalysis-other-textarea",disabled:r.disabled},null,8,p),[[n["vModelText"],f.group.expert_panel.reanalysis_other]])])):Object(n["createCommentVNode"])("",!0)]})),_:1})]})),_:1},8,["errors"])])])])}var m=r("1da1"),f=(r("96cf"),r("f96b")),h=r("033d"),g={name:"ReanalysisForm",props:{disabled:{type:Boolean,required:!1,default:!1}},data:function(){return{errors:{},otherCheckbox:!1}},computed:{group:{get:function(){return this.$store.getters["groups/currentItemOrNew"]},set:function(e){this.$store.commit("groups/addItem",e)}}},watch:{otherCheckbox:function(e){e||(this.group.expert_panel.reanalysis_other=null)}},methods:{save:function(){var e=this;return Object(m["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,e.errors={},t.next=4,f["a"].post("/api/groups/".concat(e.group.uuid,"/application/attestations/reanalysis"),e.group.expert_panel);case 4:t.next=9;break;case 6:t.prev=6,t.t0=t["catch"](0),Object(h["a"])(t.t0)&&(e.errors=t.t0.response.data.errors);case 9:case"end":return t.stop()}}),t,null,[[0,6]])})))()}}};g.render=b;t["a"]=g},a8af:function(e,t,r){"use strict";var n=r("7a23"),o={class:"flex justify-between items-center"},a=Object(n["createElementVNode"])("h4",null,"Description of Scope",-1),i={class:"mt-2"},c={key:1};function l(e,t,r,l,s,d){var u=Object(n["resolveComponent"])("edit-icon-button"),p=Object(n["resolveComponent"])("input-row"),b=Object(n["resolveComponent"])("markdown-block");return Object(n["openBlock"])(),Object(n["createElementBlock"])("div",null,[Object(n["createElementVNode"])("header",o,[a,e.hasAnyPermission(["groups-manage",["application-edit",d.group]])&&!r.editing?(Object(n["openBlock"])(),Object(n["createBlock"])(u,{key:0,onClick:t[0]||(t[0]=function(t){return e.$emit("update:editing",!0)})})):Object(n["createCommentVNode"])("",!0)]),Object(n["createElementVNode"])("div",i,[Object(n["createVNode"])(n["Transition"],{name:"fade",mode:"out-in"},{default:Object(n["withCtx"])((function(){return[r.editing?(Object(n["openBlock"])(),Object(n["createBlock"])(p,{key:0,vertical:!0,label:"Describe the scope of work of the Expert Panel including the disease area(s), genes being addressed, and any specific rational for choosing the condition(s).",errors:r.errors.scope_description},{default:Object(n["withCtx"])((function(){return[Object(n["withDirectives"])(Object(n["createElementVNode"])("textarea",{class:"w-full",rows:"10","onUpdate:modelValue":t[1]||(t[1]=function(e){return d.group.expert_panel.scope_description=e})},null,512),[[n["vModelText"],d.group.expert_panel.scope_description]])]})),_:1},8,["errors"])):(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",c,[d.group.expert_panel.scope_description?(Object(n["openBlock"])(),Object(n["createBlock"])(b,{key:0,markdown:d.group.expert_panel.scope_description},null,8,["markdown"])):(Object(n["openBlock"])(),Object(n["createElementBlock"])("p",{key:1,class:"well cursor-pointer",onClick:t[2]||(t[2]=function(){return e.showForm&&e.showForm.apply(e,arguments)})}," A description of expertise has not yet been provided. "))]))]})),_:1})])])}var s=r("ae23"),d={name:"scopeDescriptionForm",props:{editing:{type:Boolean,required:!1,default:!0},errors:{type:Object,required:!1,default:function(){return{}}}},emits:["update:editing","update:group","saved","canceled","input"],computed:{group:{get:function(){return this.$store.getters["groups/currentItem"]||new s["a"]},set:function(e){this.$store.commit("groups/addItem",e)}},scopeDescription:{get:function(){return this.group.expert_panel.scope_description},set:function(e){var t=this.group.clone();t.expert_panel.scope_description=e,this.$emit("update:group",t)}}}};d.render=l;t["a"]=d}}]);
//# sourceMappingURL=group-application~group-detail.aed6deeb.js.map