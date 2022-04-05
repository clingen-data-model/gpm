(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["application-detail~application-review~group-detail"],{"324b":function(e,t,n){"use strict";var r=n("7a23"),o={key:0},a={class:"text-lg pr-4"},c={key:1,class:"well text-center"},l={class:"text-lg pr-4"};function i(e,t,n,i,s,u){var m=Object(r["resolveComponent"])("evidence-summary"),d=Object(r["resolveComponent"])("evidence-summary-form");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[s.summaries.length>0?(Object(r["openBlock"])(),Object(r["createElementBlock"])("ul",o,[Object(r["createVNode"])(r["TransitionGroup"],{name:"slide-fade-down"},{default:Object(r["withCtx"])((function(){return[(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(u.group.expert_panel.evidence_summaries,(function(e,t){return Object(r["openBlock"])(),Object(r["createElementBlock"])("li",{class:"my-4 flex",key:t},[Object(r["createElementVNode"])("div",a,Object(r["toDisplayString"])(t+1),1),Object(r["createVNode"])(m,{summary:e,group:u.group,onSaved:u.handleSavedSummary,onDeleted:u.handleDeleted,class:"flex-1",readonly:n.readonly},null,8,["summary","group","onSaved","onDeleted","readonly"])])})),128))]})),_:1})])):s.loading?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",c,"Loading...")):(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",{key:2,class:Object(r["normalizeClass"])(["well text-center",{"cursor-pointer":!n.readonly}]),onClick:t[0]||(t[0]=function(){return u.startNewSummary&&u.startNewSummary.apply(u,arguments)})},"No example evidence summaries have been added.",2)),Object(r["withDirectives"])(Object(r["createElementVNode"])("ul",null,[(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(s.newSummaries,(function(e,t){return Object(r["openBlock"])(),Object(r["createElementBlock"])("li",{class:"my-4 flex",key:t},[Object(r["createElementVNode"])("div",l,Object(r["toDisplayString"])(t+1+s.summaries.length),1),Object(r["createVNode"])(d,{class:"flex-1",group:u.group,summary:e,onSaved:u.handleSavedSummary,onCanceled:u.cancelAdd},null,8,["group","summary","onSaved","onCanceled"])])})),128))],512),[[r["vShow"],u.adding]]),Object(r["withDirectives"])(Object(r["createElementVNode"])("div",null,[Object(r["withDirectives"])(Object(r["createElementVNode"])("button",{class:"btn btn-xs",onClick:t[1]||(t[1]=function(){return u.startNewSummary&&u.startNewSummary.apply(u,arguments)})},"Add Summary",512),[[r["vShow"],!u.adding]])],512),[[r["vShow"],!u.adding&&u.canEdit]])])}var s=n("1da1"),u=(n("c740"),n("a434"),n("96cf"),{key:0,class:"p-2 border rounded"}),m={class:"flex justify-between"},d=Object(r["createElementVNode"])("button",{class:"btn btn-xs"},"…",-1),b=Object(r["createTextVNode"])("Edit"),p=Object(r["createTextVNode"])("Delete"),j=["href"];function O(e,t,n,o,a,c){var l=Object(r["resolveComponent"])("dropdown-item"),i=Object(r["resolveComponent"])("dropdown-menu"),s=Object(r["resolveComponent"])("evidence-summary-form"),O=Object(r["resolveComponent"])("button-row"),f=Object(r["resolveComponent"])("modal-dialog");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createVNode"])(r["Transition"],{mode:"out-in",name:"fade"},{default:Object(r["withCtx"])((function(){return[a.editing?(Object(r["openBlock"])(),Object(r["createBlock"])(s,{key:1,summary:n.summary,group:n.group,onSaved:c.handleSaved,onCanceled:c.cancelEdit},null,8,["summary","group","onSaved","onCanceled"])):(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",u,[Object(r["createElementVNode"])("header",m,[Object(r["createElementVNode"])("h4",null,Object(r["toDisplayString"])(n.summary.gene.gene_symbol)+" - "+Object(r["toDisplayString"])(n.summary.variant),1),c.canEdit?(Object(r["openBlock"])(),Object(r["createBlock"])(i,{key:0,"hide-cheveron":!0,class:"relative"},{label:Object(r["withCtx"])((function(){return[d]})),default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(l,{onClick:t[0]||(t[0]=function(e){return c.edit()})},{default:Object(r["withCtx"])((function(){return[b]})),_:1}),Object(r["createVNode"])(l,{onClick:t[1]||(t[1]=function(e){return c.confirmDelete()})},{default:Object(r["withCtx"])((function(){return[p]})),_:1})]})),_:1})):Object(r["createCommentVNode"])("",!0)]),Object(r["createElementVNode"])("p",null,Object(r["toDisplayString"])(n.summary.summary),1),n.summary.vci_url?(Object(r["openBlock"])(),Object(r["createElementBlock"])("a",{key:0,class:"link",href:n.summary.vci_url,target:"_blank"}," View in the VCI ",8,j)):Object(r["createCommentVNode"])("",!0)]))]})),_:1}),(Object(r["openBlock"])(),Object(r["createBlock"])(r["Teleport"],{to:"body"},[Object(r["createVNode"])(f,{modelValue:a.showDeleteConfirm,"onUpdate:modelValue":t[2]||(t[2]=function(e){return a.showDeleteConfirm=e}),title:"You are about to delete an example evidence summary."},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(" You are about to delete an evidence summary for "+Object(r["toDisplayString"])(n.summary.gene.gene_symbol)+" - "+Object(r["toDisplayString"])(n.summary.variant)+". Are you sure you want to continue? ",1),Object(r["createVNode"])(O,{onSubmit:c.deleteSummary,onCancel:c.cancelDelete,"submit-text":"Delete Summary"},null,8,["onSubmit","onCancel"])]})),_:1},8,["modelValue"])]))])}n("99af");var f=n("f96b"),g={class:"flex space-x-2"},h=Object(r["createElementVNode"])("option",{value:null},"Select...",-1),y=["value"];function v(e,t,n,o,a,c){var l=Object(r["resolveComponent"])("input-row"),i=Object(r["resolveComponent"])("button-row");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createElementVNode"])("div",g,[Object(r["createVNode"])(l,{class:"mt-0 mb-0",vertical:!0,label:"Gene",errors:a.errors.gene_id},{default:Object(r["withCtx"])((function(){return[Object(r["withDirectives"])(Object(r["createElementVNode"])("select",{"onUpdate:modelValue":t[0]||(t[0]=function(e){return a.summaryClone.gene_id=e})},[h,(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(a.groupGenes,(function(e){return Object(r["openBlock"])(),Object(r["createElementBlock"])("option",{value:e.id,key:e.id},Object(r["toDisplayString"])(e.gene.gene_symbol),9,y)})),128))],512),[[r["vModelSelect"],a.summaryClone.gene_id]])]})),_:1},8,["errors"]),Object(r["createVNode"])(l,{class:"mt-0 mb-0",modelValue:a.summaryClone.variant,"onUpdate:modelValue":t[1]||(t[1]=function(e){return a.summaryClone.variant=e}),vertical:!0,label:"Variant",errors:a.errors.variant},null,8,["modelValue","errors"])]),Object(r["createVNode"])(l,{class:"mt-0 mb-0",label:"Summary",vertical:!0,errors:a.errors.summary},{default:Object(r["withCtx"])((function(){return[Object(r["withDirectives"])(Object(r["createElementVNode"])("textarea",{rows:"5",class:"w-full","onUpdate:modelValue":t[2]||(t[2]=function(e){return a.summaryClone.summary=e})},null,512),[[r["vModelText"],a.summaryClone.summary]])]})),_:1},8,["errors"]),Object(r["createVNode"])(l,{class:"mt-0",modelValue:a.summaryClone.vci_url,"onUpdate:modelValue":t[3]||(t[3]=function(e){return a.summaryClone.vci_url=e}),label:"VCI URL",vertical:!0,"input-class":"w-full",errors:a.errors.vci_url},null,8,["modelValue","errors"]),Object(r["createVNode"])(i,{"submit-text":"save",onSubmit:c.save,onCancel:c.cancel},null,8,["onSubmit","onCancel"])])}var w=n("5530"),V=n("033d"),N={name:"EvidenceSummaryForm",props:{group:{required:!0,type:Object},summary:{required:!0,type:Object}},emits:["saved","canceled"],data:function(){return{groupGenes:[],summaryClone:{gene:{}},errors:{}}},computed:{},watch:{group:{immediate:!0,handler:function(){this.group&&this.group.id&&this.getGroupGenes()}},summary:{immediate:!0,handler:function(){this.summaryClone=Object(w["a"])({},this.summary)}}},methods:{save:function(){var e=this;return Object(s["a"])(regeneratorRuntime.mark((function t(){var n,r,o;return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,n="/api/groups/".concat(e.group.uuid,"/expert-panel/evidence-summaries"),r="post",e.summaryClone.id&&(n+="/".concat(e.summaryClone.id),r="put"),t.next=6,Object(f["a"])({method:r,url:n,data:e.summaryClone}).then((function(e){return e.data.data}));case 6:o=t.sent,e.$emit("saved",o),e.$store.commit("pushSuccess","Saved example evidence summary"),e.editing=!1,t.next=15;break;case 12:t.prev=12,t.t0=t["catch"](0),Object(V["a"])(t.t0)&&(e.errors=t.t0.response.data.errors);case 15:case"end":return t.stop()}}),t,null,[[0,12]])})))()},cancel:function(){this.initSummaryClone(),this.$emit("canceled")},edit:function(e){e.editing=!0},getGroupGenes:function(){var e=this;return Object(s["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,f["a"].get("/api/groups/".concat(e.group.uuid,"/expert-panel/genes")).then((function(e){return e.data}));case 2:e.groupGenes=t.sent;case 3:case"end":return t.stop()}}),t)})))()},initSummaryClone:function(){this.summaryClone={gene:{}}}}},k=n("6b0d"),x=n.n(k);const C=x()(N,[["render",v]]);var S=C,_={components:{EvidenceSummaryForm:S},name:"EvidenceSummary",props:{summary:{required:!0,type:Object},group:{required:!0,type:Object},readonly:{type:Boolean,required:!1,default:!1}},emits:["edit","saved","deleted"],data:function(){return{editing:!1,showDeleteConfirm:!1}},computed:{canEdit:function(){return this.hasAnyPermission(["ep-applications-manage",["application-edit",this.group]])&&!this.readonly}},methods:{handleSaved:function(e){this.$emit("saved",e),this.editing=!1},edit:function(){this.editing=!0},cancelEdit:function(){this.editing=!1},confirmDelete:function(){this.showDeleteConfirm=!0},deleteSummary:function(){var e=this;return Object(s["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,f["a"].delete("/api/groups/".concat(e.group.uuid,"/application/evidence-summaries/").concat(e.summary.id));case 3:e.$emit("deleted",e.summary),t.next=9;break;case 6:t.prev=6,t.t0=t["catch"](0),console.error(t.t0);case 9:e.showDeleteConfirm=!1;case 10:case"end":return t.stop()}}),t,null,[[0,6]])})))()},cancelDelete:function(){this.showDeleteConfirm=!1}}};const E=x()(_,[["render",O]]);var B=E,D={name:"EvidenceSummaryList",components:{EvidenceSummary:B,EvidenceSummaryForm:S},emits:["summaries-added"],props:{readonly:{type:Boolean,required:!1,default:!1}},data:function(){return{newSummaries:[],errors:{},summaries:[],showDeleteConfirm:!1,selectedSummary:{gene:{}},loading:!1}},computed:{group:{get:function(){return this.$store.getters["groups/currentItemOrNew"]},set:function(e){this.$store.commit("groups/addItem",e)}},meetsRequirements:function(){return this.summaries.length>4},adding:function(){return this.newSummaries.length>0},canEdit:function(){return this.hasAnyPermission(["ep-applications-manage",["application-edit",this.group]])&&!this.readonly}},watch:{group:{immediate:!0,handler:function(){this.group&&this.group.id&&this.getEvidenceSummaries()}}},methods:{getEvidenceSummaries:function(){var e=this;return Object(s["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return e.loading=!0,t.prev=1,t.next=4,e.$store.dispatch("groups/getEvidenceSummaries",e.group);case 4:e.summaries=t.sent,t.next=10;break;case 7:t.prev=7,t.t0=t["catch"](1),console.log(t.t0);case 10:e.loading=!1;case 11:case"end":return t.stop()}}),t,null,[[1,7]])})))()},startNewSummary:function(){this.readonly||this.newSummaries.push({gene:null,variant:null,summary:null})},clearNewSummaries:function(){this.newSummaries=[]},handleSavedSummary:function(){this.getEvidenceSummaries(),this.clearNewSummaries(),this.$emit("summaries-added")},handleDeleted:function(e){var t=this.summaries.findIndex((function(t){return t.id==e.id}));t>-1&&this.summaries.splice(t,1),this.getEvidenceSummaries()},cancelAdd:function(){this.clearNewSummaries()},mergeSummary:function(e){var t=this.summaries.findIndex((function(t){return t.id==e.id}));t>-1?this.summaries.splice(t,1,e):this.summaries.push(e)}}};const P=x()(D,[["render",i]]);t["a"]=P},"83a1":function(e,t,n){"use strict";n("d81d"),n("b0c0"),n("a15b");var r={name:"ApplicationStepReview",props:{},data:function(){return{}},computed:{group:function(){return this.$store.getters["groups/currentItemOrNew"]},expertPanel:function(){return this.group.expert_panel},isGcep:function(){return this.group.isGcep()},isVcep:function(){return this.group.isVcep()},members:function(){var e=this;return this.group.members.map((function(t){return{id:t.id,name:t.person.name,institution:t.person.institution?t.person.institution.name:null,credentials:t.person.credentials,expertise:t.expertise,roles:t.roles.map((function(e){return e.name})).join(", "),coi_completed:e.formatDate(t.coi_last_completed)}}))}},methods:{}};const o=r;t["a"]=o},abba:function(e,t,n){"use strict";var r=n("7a23"),o=Object(r["createElementVNode"])("div",{class:"font-bold text-lg mb-4"}," COMING SOON: Summary of CSpec specfications display here. ",-1),a=["disabled"],c=Object(r["createTextVNode"])(" Go to the CSpec Registry ");function l(e,t,n,l,i,s){var u=Object(r["resolveComponent"])("icon-external-link");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[o,Object(r["createElementVNode"])("button",{onClick:t[0]||(t[0]=function(){return s.goToCspec&&s.goToCspec.apply(s,arguments)}),class:"btn btn-xl blue",target:"cspec",disabled:n.readonly},[c,Object(r["createVNode"])(u,{class:"inline"})],8,a)])}n("a9e3");var i={name:"Cspec Summary",props:{readonly:{type:Boolean,default:!1}},data:function(){return{sort:{field:"id",desc:!1},specFields:[{name:"gene_symbol",type:String,sortable:!0},{name:"disease_name",type:String,sortable:!0},{name:"status.name",label:"Status",type:String,sortable:!0},{name:"updated_at",label:"Last Updated",type:Date,sortable:!0},{name:"id",label:"",type:Number,sortable:!1}]}},computed:{group:function(){return this.$store.getters["groups/currentItemOrNew"]},hasSpecifications:function(){return!0},specifications:function(){return this.group.expert_panel.specifications}},watch:{group:function(e){this.$store.dispatch("groups/getSpecifications",e)}},methods:{goToCspec:function(){window.location="https://cspec.genome.network/"}},mounted:function(){this.$store.dispatch("groups/getSpecifications",this.group)}},s=n("6b0d"),u=n.n(s);const m=u()(i,[["render",l]]);t["a"]=m},d5fa:function(e,t,n){"use strict";n("b0c0"),n("a15b"),n("d81d");var r=n("7a23"),o={class:"application-review bg-gray-100 p-2"},a=Object(r["createElementVNode"])("h2",null,"Basic Information",-1),c=Object(r["createElementVNode"])("h2",null,"Membership",-1),l={key:0,class:"mt-6"},i=Object(r["createElementVNode"])("h4",null,"Expertise of VCEP members",-1),s=Object(r["createElementVNode"])("h2",null,"Scope of Work",-1),u=Object(r["createElementVNode"])("h3",null,"Genes",-1),m={class:"mb-6"},d={key:0},b=Object(r["createElementVNode"])("h3",null,"Description of scope",-1),p={key:0},j=Object(r["createElementVNode"])("h2",null,"Plans for Ongoing Review and Descrepency Resolution",-1),O={class:"flex-none"},f={key:0,class:"mt-1"},g=Object(r["createElementVNode"])("em",null,"Details:",-1),h={key:1},y=Object(r["createElementVNode"])("h2",null,"Attestations",-1),v={key:2},w=Object(r["createElementVNode"])("h2",null,"Attestations",-1);function V(e,t,n,V,N,k){var x=this,C=Object(r["resolveComponent"])("object-dictionary"),S=Object(r["resolveComponent"])("dictionary-row"),_=Object(r["resolveComponent"])("simple-table"),E=Object(r["resolveComponent"])("markdown-block");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",o,[Object(r["createElementVNode"])("section",null,[a,Object(r["createVNode"])(C,{obj:k.basicInfo,"label-class":"w-40 font-bold"},null,8,["obj"]),Object(r["createVNode"])(S,{label:"CDWG","label-class":"w-40 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(x.group.parent?x.group.parent.name:"--"),1)]})),_:1})]),Object(r["createElementVNode"])("section",null,[c,Object(r["createVNode"])(_,{data:e.members,"key-by":"id",class:"print:text-xs text-sm"},null,8,["data"]),e.isVcep?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",l,[i,Object(r["createElementVNode"])("blockquote",null,[Object(r["createVNode"])(E,{markdown:e.expertPanel.membership_description},null,8,["markdown"])])])):Object(r["createCommentVNode"])("",!0)]),Object(r["createElementVNode"])("section",null,[s,u,Object(r["createElementVNode"])("div",m,[e.isGcep?(Object(r["openBlock"])(),Object(r["createElementBlock"])("p",d,Object(r["toDisplayString"])(e.expertPanel.genes.map((function(e){return e.gene.gene_symbol})).join(", ")),1)):Object(r["createCommentVNode"])("",!0),e.isVcep?(Object(r["openBlock"])(),Object(r["createBlock"])(_,{key:1,data:e.expertPanel.genes.map((function(e){return{id:e.id,gene:e.gene.gene_symbol,disease:e.disease_name}})),"key-by":"id","hide-columns":["id"]},null,8,["data"])):Object(r["createCommentVNode"])("",!0)]),b,Object(r["createElementVNode"])("blockquote",null,[Object(r["createVNode"])(E,{markdown:e.expertPanel.scope_description},null,8,["markdown"])])]),e.isGcep?(Object(r["openBlock"])(),Object(r["createElementBlock"])("section",p,[j,Object(r["createVNode"])(S,{label:"Selected protocol","label-class":"w-48 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",O,[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.expertPanel.curation_review_protocol?e.titleCase(e.expertPanel.curation_review_protocol.full_name):null)+" ",1),100==e.expertPanel.curation_review_protocol_id?(Object(r["openBlock"])(),Object(r["createElementBlock"])("p",f,[g,Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(e.expertPanel.curation_review_protocol_other),1)])):Object(r["createCommentVNode"])("",!0)])]})),_:1})])):Object(r["createCommentVNode"])("",!0),e.isGcep?(Object(r["openBlock"])(),Object(r["createElementBlock"])("section",h,[y,Object(r["createVNode"])(S,{label:"GCEP Attestation Signed","label-class":"w-52 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.formatDate(e.expertPanel.gcep_attestation_date)),1)]})),_:1}),Object(r["createVNode"])(S,{label:"GCI Training Date","label-class":"w-52 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.formatDate(e.expertPanel.gci_training_date)),1)]})),_:1}),Object(r["createVNode"])(S,{label:"NHGRI Attestation Signed","label-class":"w-52 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.formatDate(e.expertPanel.nhgri_attestation_date)),1)]})),_:1})])):Object(r["createCommentVNode"])("",!0),e.isVcep?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",v,[Object(r["createElementVNode"])("section",null,[w,Object(r["createVNode"])(S,{label:"Reanalysis and Descrepency Resolution Attestation Signed","label-class":"w-52 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.formatDate(e.expertPanel.reanalysis_attestation_date)),1)]})),_:1}),Object(r["createVNode"])(S,{label:"NHGRI Attestation Signed","label-class":"w-60 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.formatDate(e.expertPanel.nhgri_attestation_date)),1)]})),_:1})])])):Object(r["createCommentVNode"])("",!0)])}var N=n("83a1"),k={name:"DefinitionReview",extends:N["a"],computed:{basicInfo:function(){return{type:this.group.type.name?this.group.type.name.toUpperCase():"",long_base_name:this.expertPanel.long_base_name,short_base_name:this.expertPanel.short_base_name}}}},x=n("6b0d"),C=n.n(x);const S=C()(k,[["render",V]]);t["a"]=S},f7ba:function(e,t,n){"use strict";n("a15b"),n("d81d"),n("b0c0");var r=n("7a23"),o={class:"application-review p-2 bg-gray-100"},a={key:0},c=Object(r["createElementVNode"])("h2",null,"Plans for Ongoing Review and Descrepency Resolution",-1),l={class:"w-full"},i={key:0,class:"mt-1"},s=Object(r["createElementVNode"])("em",null,"Details:",-1),u={key:1},m=Object(r["createElementVNode"])("h2",null,"Evidence Summaries",-1),d={key:2},b=Object(r["createElementVNode"])("h2",null,"Core Approval Member, Trained Biocurator, and Biocurator Trainer Designation",-1);function p(e,t,n,p,j,O){var f=this,g=Object(r["resolveComponent"])("dictionary-row"),h=Object(r["resolveComponent"])("markdown-block"),y=Object(r["resolveComponent"])("evidence-summary-list");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",o,[e.expertPanel.has_approved_pilot?(Object(r["openBlock"])(),Object(r["createElementBlock"])("section",a,[c,Object(r["createVNode"])(g,{label:"Selected protocol",labelWidthClass:"w-48 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",l,[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.expertPanel.curation_review_protocol?e.titleCase(e.expertPanel.curation_review_protocol.full_name):null)+" ",1),100==e.expertPanel.curation_review_protocol_id?(Object(r["openBlock"])(),Object(r["createElementBlock"])("p",i,[s,Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(e.expertPanel.curation_review_protocol_other),1)])):Object(r["createCommentVNode"])("",!0)])]})),_:1}),Object(r["createVNode"])(g,{label:"Notes",labelWidthClass:"w-48 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(h,{markdown:e.expertPanel.curation_review_process_notes},null,8,["markdown"])]})),_:1})])):Object(r["createCommentVNode"])("",!0),e.expertPanel.has_approved_pilot?(Object(r["openBlock"])(),Object(r["createElementBlock"])("section",u,[m,Object(r["createVNode"])(y,{readonly:!0})])):Object(r["createCommentVNode"])("",!0),e.expertPanel.has_approved_pilot?(Object(r["openBlock"])(),Object(r["createElementBlock"])("section",d,[b,Object(r["createVNode"])(g,{label:"Core Approval Members",labelWidthClass:"w-48 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(f.group.coreApprovalMembers.map((function(e){return e.person.name})).join(", ")),1)]})),_:1}),Object(r["createVNode"])(g,{label:"Biocurator Trainers",labelWidthClass:"w-48 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(f.group.biocuratorTrainers.map((function(e){return e.person.name})).join(", ")),1)]})),_:1}),Object(r["createVNode"])(g,{label:"Trained Biocurators",labelWidthClass:"w-48 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(f.group.trainedBiocurators.map((function(e){return e.person.name})).join(", ")),1)]})),_:1})])):Object(r["createCommentVNode"])("",!0)])}var j=n("83a1"),O=n("324b"),f={name:"SustainedCurationReview",extends:j["a"],components:{EvidenceSummaryList:O["a"]}},g=n("6b0d"),h=n.n(g);const y=h()(f,[["render",p]]);t["a"]=y}}]);
//# sourceMappingURL=application-detail~application-review~group-detail.87cd5568.js.map