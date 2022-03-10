(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-187bb237"],{"0241":function(e,t,o){"use strict";o("ca76")},"04e9":function(e,t,o){},"0945":function(e,t,o){"use strict";o("a15b"),o("b0c0"),o("4e82"),o("d81d");var r=o("7a23"),n={class:"flex justify-between items-baseline"},c={class:"flex space-x-2 items-baseline"},l=Object(r["createElementVNode"])("h2",null,"Members",-1),a={class:"flex space-x-2 items-baseline"},i=Object(r["createTextVNode"])("Add Member"),s={key:1},u=["href"],d={key:2},b=["href"],m={key:0,class:"flex justify-between px-2 space-x-2 bg-blue-200 rounded-lg"},p={class:"flex-1"},f=Object(r["createElementVNode"])("option",{value:null},"Select…",-1),O=["value"],j={class:"flex-1 py-2"},h={class:"flex-1 py-2"},v={class:"mt-3 py-2 w-full overflow-x-auto"},g=["onClick"],w={class:"flex space-x-2"},_={key:0},V=["onClick"],C={class:"flex space-x-2 items-center"},k=Object(r["createElementVNode"])("button",{class:"btn btn-xs"},"…",-1),y=["onClick"],x=["onClick"],N=["onClick"],E={key:1,class:"well"},B={class:"text-lg"},D={class:"text-lg"},R=Object(r["createElementVNode"])("p",null,[Object(r["createElementVNode"])("strong",null,"This cannot be undone.")],-1);function M(e,t,o,M,S,P){var T=Object(r["resolveComponent"])("icon-filter"),I=Object(r["resolveComponent"])("router-link"),q=Object(r["resolveComponent"])("input-row"),F=Object(r["resolveComponent"])("checkbox"),$=Object(r["resolveComponent"])("icon-cheveron-right"),A=Object(r["resolveComponent"])("icon-cheveron-down"),U=Object(r["resolveComponent"])("icon-view"),G=Object(r["resolveComponent"])("icon-exclamation"),z=Object(r["resolveComponent"])("dropdown-item"),Q=Object(r["resolveComponent"])("dropdown-menu"),L=Object(r["resolveComponent"])("icon-notification"),W=Object(r["resolveComponent"])("popover"),Y=Object(r["resolveComponent"])("member-preview"),J=Object(r["resolveComponent"])("data-table"),K=Object(r["resolveComponent"])("button-row"),H=Object(r["resolveComponent"])("modal-dialog"),X=Object(r["resolveComponent"])("coi-detail");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createElementVNode"])("head",n,[Object(r["createElementVNode"])("div",c,[l,P.group.members.length>0?(Object(r["openBlock"])(),Object(r["createElementBlock"])("button",{key:0,ref:"filterToggleButton",class:Object(r["normalizeClass"])(["px-3 py-2 rounded-t transition-color",{"rounded-b":!S.showFilter,"bg-blue-200":S.showFilter}]),onClick:t[0]||(t[0]=function(){return P.toggleFilter&&P.toggleFilter.apply(P,arguments)})},[Object(r["createVNode"])(T)],2)):Object(r["createCommentVNode"])("",!0)]),Object(r["createElementVNode"])("div",a,[e.hasAnyPermission([["members-invite",P.group],"groups-manage","ep-applications-manage","annual-updates-manage"])&&!o.readonly?(Object(r["openBlock"])(),Object(r["createBlock"])(I,{key:0,class:"btn btn-xs",ref:"addMemberButton",to:e.append(e.$route.path,"members/add")},{default:Object(r["withCtx"])((function(){return[i]})),_:1},8,["to"])):Object(r["createCommentVNode"])("",!0),P.group.isEp()?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",s,[Object(r["createElementVNode"])("a",{class:"btn btn-xs",href:"/report/groups/".concat(P.group.uuid,"/coi-report")},"Get COI Report",8,u)])):Object(r["createCommentVNode"])("",!0),P.hasAnyMemberPermission(["groups-manage",["info-edit",P.group]])&&e.$store.state.systemInfo.app.features.email_from_member_list?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",d,[Object(r["createElementVNode"])("a",{href:"mailto:".concat(P.filteredEmails.join(", ")),class:"btn btn-xs",onClick:t[1]||(t[1]=function(){return e.initEmailWithFiltered&&e.initEmailWithFiltered.apply(e,arguments)})},"Email filtered",8,b)])):Object(r["createCommentVNode"])("",!0)])]),Object(r["createVNode"])(r["Transition"],{name:"slide-fade-down"},{default:Object(r["withCtx"])((function(){return[P.group.members.length>0?Object(r["withDirectives"])((Object(r["openBlock"])(),Object(r["createElementBlock"])("div",m,[Object(r["createElementVNode"])("div",p,[Object(r["createVNode"])(q,{label:"Keyword",type:"text",modelValue:S.filters.keyword,"onUpdate:modelValue":t[2]||(t[2]=function(e){return S.filters.keyword=e}),"label-width-class":"w-20"},null,8,["modelValue"]),Object(r["createVNode"])(q,{label:"Role","label-width-class":"w-20"},{default:Object(r["withCtx"])((function(){return[Object(r["withDirectives"])(Object(r["createElementVNode"])("select",{"onUpdate:modelValue":t[3]||(t[3]=function(e){return S.filters.roleId=e})},[f,(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(P.roles,(function(e){return Object(r["openBlock"])(),Object(r["createElementBlock"])("option",{key:e.id,value:e.id},Object(r["toDisplayString"])(e.name),9,O)})),128))],512),[[r["vModelSelect"],S.filters.roleId]])]})),_:1})]),Object(r["createElementVNode"])("div",j,[Object(r["createVNode"])(F,{class:"block",label:"Needs COI",modelValue:S.filters.needsCoi,"onUpdate:modelValue":t[4]||(t[4]=function(e){return S.filters.needsCoi=e})},null,8,["modelValue"])]),Object(r["createElementVNode"])("div",h,[Object(r["createVNode"])(F,{class:"block",label:"Hide Alumns",modelValue:S.filters.hideAlumns,"onUpdate:modelValue":t[5]||(t[5]=function(e){return S.filters.hideAlumns=e})},null,8,["modelValue"])])],512)),[[r["vShow"],S.showFilter]]):Object(r["createCommentVNode"])("",!0)]})),_:1}),Object(r["createElementVNode"])("div",v,[P.group.members.length>0?(Object(r["openBlock"])(),Object(r["createBlock"])(J,{key:0,fields:P.fieldsForGroupType,data:P.filteredMembers,sort:M.sort,"onUpdate:sort":t[7]||(t[7]=function(e){return M.sort=e}),detailRows:!0,"row-class":function(e){return"cursor-pointer"+(e.isRetired?" retired-member":"")},onRowClick:P.goToMember},{"cell-id":Object(r["withCtx"])((function(e){var t=e.item;return[Object(r["createElementVNode"])("button",{onClick:Object(r["withModifiers"])((function(e){return P.toggleItemDetails(t)}),["stop"]),class:"w-9 align-center block -mx-3"},[t.showDetails?Object(r["createCommentVNode"])("",!0):(Object(r["openBlock"])(),Object(r["createBlock"])($,{key:0,class:"m-auto cursor-pointer"})),t.showDetails?(Object(r["openBlock"])(),Object(r["createBlock"])(A,{key:1,class:"m-auto cursor-pointer"})):Object(r["createCommentVNode"])("",!0)],8,g)]})),"cell-roles":Object(r["withCtx"])((function(t){var o=t.value;return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.titleCase(o.map((function(e){return e.name})).join(", "))),1)]})),"cell-coi_last_completed":Object(r["withCtx"])((function(t){var o=t.item;return[Object(r["createElementVNode"])("div",w,[o.coi_last_completed?(Object(r["openBlock"])(),Object(r["createElementBlock"])("span",_,Object(r["toDisplayString"])(e.formatDate(o.coi_last_completed)),1)):Object(r["createCommentVNode"])("",!0),o.latest_coi_id?(Object(r["openBlock"])(),Object(r["createElementBlock"])("button",{key:1,class:"link cursor-pointer",onClick:Object(r["withModifiers"])((function(e){return P.viewCoi(o.latest_coi_id)}),["stop"])},[Object(r["createVNode"])(U)],8,V)):Object(r["createCommentVNode"])("",!0),null===!o.coi_last_completed||o.coi_last_completed<e.yearAgo()?(Object(r["openBlock"])(),Object(r["createBlock"])(G,{key:2,class:Object(r["normalizeClass"])(P.getCoiDateStyle(o))},null,8,["class"])):Object(r["createCommentVNode"])("",!0)])]})),"cell-actions":Object(r["withCtx"])((function(n){var c=n.item;return[Object(r["createElementVNode"])("div",C,[P.hasAnyMemberPermission()&&!o.readonly?(Object(r["openBlock"])(),Object(r["createBlock"])(Q,{key:0,"hide-cheveron":!0,class:"relative"},{label:Object(r["withCtx"])((function(){return[k]})),default:Object(r["withCtx"])((function(){return[e.hasAnyPermission([["members-update",P.group],"groups-manage"])?(Object(r["openBlock"])(),Object(r["createBlock"])(z,{key:0},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",{onClick:function(e){return P.editMember(c)}},"Update membership",8,y)]})),_:2},1024)):Object(r["createCommentVNode"])("",!0),e.hasAnyPermission([["members-retire",P.group],"groups-manage"])?(Object(r["openBlock"])(),Object(r["createBlock"])(z,{key:1},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",{onClick:function(e){return P.confirmRetireMember(c)}},"Retire from group",8,x)]})),_:2},1024)):Object(r["createCommentVNode"])("",!0),e.hasAnyPermission([["members-remove",P.group],"groups-manage"])?(Object(r["openBlock"])(),Object(r["createBlock"])(z,{key:2},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",{onClick:function(e){return P.confirmRemoveMember(c)}},"Remove from group",8,N)]})),_:2},1024)):Object(r["createCommentVNode"])("",!0)]})),_:2},1024)):Object(r["createCommentVNode"])("",!0),Object(r["createVNode"])(W,{hover:"",arrow:"",content:"Receives notifications about this group.",placement:"top"},{default:Object(r["withCtx"])((function(){return[c.is_contact?(Object(r["openBlock"])(),Object(r["createBlock"])(L,{key:0,width:12,height:12,"icon-name":"Is a group contact",onClick:t[6]||(t[6]=Object(r["withModifiers"])((function(){}),["stop"]))})):Object(r["createCommentVNode"])("",!0)]})),_:2},1024)])]})),detail:Object(r["withCtx"])((function(e){var t=e.item;return[Object(r["createVNode"])(Y,{member:t,group:P.group},null,8,["member","group"])]})),_:1},8,["fields","data","sort","row-class","onRowClick"])):(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",E," This group does not yet have any members. "))]),(Object(r["openBlock"])(),Object(r["createBlock"])(r["Teleport"],{to:"body"},[Object(r["createVNode"])(H,{modelValue:S.showConfirmRetire,"onUpdate:modelValue":t[8]||(t[8]=function(e){return S.showConfirmRetire=e}),size:"xs",title:"Retire ".concat(P.selectedMemberName,"?")},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("p",B," Are you sure you want to retire "+Object(r["toDisplayString"])(P.selectedMemberName)+" from this group? ",1),Object(r["createVNode"])(K,{onSubmit:P.retireMember,onCancel:P.cancelRetire,"submit-text":"Retire Member"},null,8,["onSubmit","onCancel"])]})),_:1},8,["modelValue","title"]),Object(r["createVNode"])(H,{modelValue:S.showConfirmRemove,"onUpdate:modelValue":t[9]||(t[9]=function(e){return S.showConfirmRemove=e}),size:"xs",title:"Remove ".concat(P.selectedMemberName,"?")},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("p",D," Are you sure you want to remove "+Object(r["toDisplayString"])(P.selectedMemberName)+" from this group?",1),R,Object(r["createVNode"])(K,{onSubmit:P.removeMember,onCancel:P.cancelRemove,"submit-text":"Remove Member"},null,8,["onSubmit","onCancel"])]})),_:1},8,["modelValue","title"]),Object(r["createVNode"])(H,{modelValue:S.showCoiDetail,"onUpdate:modelValue":t[10]||(t[10]=function(e){return S.showCoiDetail=e}),size:"xl"},{default:Object(r["withCtx"])((function(){return[S.coi?(Object(r["openBlock"])(),Object(r["createBlock"])(X,{key:0,coi:S.coi,group:P.group},null,8,["coi","group"])):Object(r["createCommentVNode"])("",!0)]})),_:1},8,["modelValue"])]))])}var S=o("1da1"),P=o("2909"),T=(o("96cf"),o("a9e3"),o("4de4"),o("d3b7"),o("99af"),o("a434"),o("c740"),o("4a91")),I=o("db04"),q=o("5257"),F={class:"px-8 py-4 inset"},$=Object(r["createTextVNode"])(" RETIRED "),A={class:"md:flex flex-wrap space-x-4 text-sm"},U={class:"flex-1 md:flex flex-wrap"},G={class:"flex-1 mr-8"},z={class:"flex-1 mr-4"},Q={class:"mt-2"},L=Object(r["createElementVNode"])("h4",null,"Roles:",-1),W={class:"ml-2"},Y={key:0},J=Object(r["createElementVNode"])("h4",null,"Biocurator Training:",-1),K={class:"ml-2"},H={class:"mt-2"},X=Object(r["createElementVNode"])("h4",null,"Extra Permissions:",-1),Z={class:"ml-2"},ee=Object(r["createElementVNode"])("div",null,null,-1),te=Object(r["createTextVNode"])(" View profile ");function oe(e,t,o,n,c,l){var a=Object(r["resolveComponent"])("static-alert"),i=Object(r["resolveComponent"])("profile-picture"),s=Object(r["resolveComponent"])("note"),u=Object(r["resolveComponent"])("dictionary-row"),d=Object(r["resolveComponent"])("object-dictionary"),b=Object(r["resolveComponent"])("icon-checkmark"),m=Object(r["resolveComponent"])("router-link");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",F,[o.member.isRetired?(Object(r["openBlock"])(),Object(r["createBlock"])(a,{key:0,variant:"warning",class:"mb-3 float-right"},{default:Object(r["withCtx"])((function(){return[$]})),_:1})):Object(r["createCommentVNode"])("",!0),Object(r["createElementVNode"])("div",A,[Object(r["createElementVNode"])("div",null,[Object(r["createVNode"])(i,{person:o.member.person},null,8,["person"]),Object(r["createVNode"])(s,null,{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])("member id: "+Object(r["toDisplayString"])(o.member.id),1)]})),_:1})]),Object(r["createElementVNode"])("div",U,[Object(r["createElementVNode"])("div",G,[Object(r["createVNode"])(u,{label:"Email"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(o.member.person.email),1)]})),_:1}),Object(r["createVNode"])(u,{label:"Institution"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(o.member.person.institution_id?o.member.person.institution.name:"--"),1)]})),_:1}),Object(r["createVNode"])(u,{label:"Credentials"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(o.member.person.credentials),1)]})),_:1}),Object(r["createVNode"])(d,{obj:o.member,only:["expertise","notes"]},null,8,["obj"]),Object(r["createVNode"])(u,{label:"Start - End"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(n.formatDate(o.member.start_date))+" - "+Object(r["toDisplayString"])(n.formatDate(o.member.end_date)||"present"),1)]})),_:1})]),Object(r["createElementVNode"])("div",z,[Object(r["createElementVNode"])("div",Q,[L,Object(r["createElementVNode"])("div",W,Object(r["toDisplayString"])(o.member.roles.length>0?o.member.roles.map((function(t){return e.titleCase(t.name)})).join(", "):"--"),1)]),o.member.hasRole("biocurator")?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",Y,[J,Object(r["createElementVNode"])("div",K,[Object(r["createVNode"])(u,{label:"Level 1 training"},{default:Object(r["withCtx"])((function(){return[o.member.training_level_1?(Object(r["openBlock"])(),Object(r["createBlock"])(b,{key:0,class:"text-green-700"})):Object(r["createCommentVNode"])("",!0)]})),_:1}),Object(r["createVNode"])(u,{label:"Level 2 training"},{default:Object(r["withCtx"])((function(){return[o.member.training_level_2?(Object(r["openBlock"])(),Object(r["createBlock"])(b,{key:0,class:"text-green-700"})):Object(r["createCommentVNode"])("",!0)]})),_:1})])])):Object(r["createCommentVNode"])("",!0),Object(r["createElementVNode"])("div",H,[X,Object(r["createElementVNode"])("div",Z,Object(r["toDisplayString"])(o.member.permissions.length>0?o.member.permissions.map((function(e){return e.name})).join(", "):"--"),1)])])]),ee]),Object(r["createVNode"])(m,{class:"link",to:{name:"PersonDetail",params:{uuid:this.member.person.uuid}}},{default:Object(r["withCtx"])((function(){return[te]})),_:1},8,["to"])])}var re=o("86ed"),ne=o("ae23"),ce={class:"w-24 border border-gray-300 bg-white"},le=["src"];function ae(e,t,o,n,c,l){var a=Object(r["resolveComponent"])("icon-user");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",ce,[o.person.profile_photo_path?(Object(r["openBlock"])(),Object(r["createElementBlock"])("img",{key:0,src:o.person.profile_photo_path},null,8,le)):(Object(r["openBlock"])(),Object(r["createBlock"])(a,{key:1,height:"96",width:"96","icon-color":"#888"}))])}var ie=o("da07"),se={name:"ProfilePicture",props:{person:{required:!0,type:ie["a"]}},data:function(){return{}},computed:{hasProfilePhoto:function(){return null!==this.person.profile_photo_path}},methods:{}},ue=o("6b0d"),de=o.n(ue);const be=de()(se,[["render",ae]]);var me=be,pe=o("e328"),fe={name:"MemberPreview",components:{ProfilePicture:me},props:{member:{type:re["a"],required:!0},group:{type:ne["a"],required:!0}},emits:["edit"],setup:function(){return{formatDate:pe["b"]}}};const Oe=de()(fe,[["render",oe]]);var je=Oe,he=o("8c98"),ve={name:"MemberList",components:{MemberPreview:je,CoiDetail:he["a"]},props:{readonly:{type:Boolean,default:!1}},data:function(){return{showFilter:!1,showConfirmRetire:!1,showConfirmRemove:!1,filters:{keyword:null,roleId:null,needsCoi:null,needsTraining:null,hideAlumns:!0},tableFields:[{name:"id",label:"",type:Number,sortable:!1},{name:"person.first_name",label:"First",type:String,sortable:!0},{name:"person.last_name",label:"Last",type:String,sortable:!0},{name:"person.timezone",label:"Closest City (for timezone)",type:String,sortable:!0},{name:"roles",label:"Roles",sortable:!1},{name:"coi_last_completed",label:"COI Completed",type:Date,sortable:!0},{name:"actions",label:"",type:Object,sortable:!1}],selectedMember:null,hideAlumns:!0,members:[],showCoiDetail:!1,coi:null}},computed:{group:function(){return this.$store.getters["groups/currentItemOrNew"]},filteredMembers:function(){var e=this;return this.group.members?this.group.members.filter((function(t){return e.matchesFilters(t)})).filter((function(t){return e.filters.hideAlumns?null===t.end_date:t})):[]},filteredEmails:function(){return this.filteredMembers.map((function(e){return"".concat(e.person.name," <").concat(e.person.email,">")}))},fieldsForGroupType:function(){var e=Object(P["a"])(this.tableFields);return this.group.isEp()||e.splice(e.findIndex((function(e){return"coi_last_completed"==e.name})),1),e},roles:function(){return T.groups.roles},coiCuttoff:function(){var e=new Date;return e.setFullYear(e.getFullYear()-1),e},selectedMemberName:function(){return this.selectedMember?this.selectedMember.person.name:null}},watch:{group:{immediate:!0,handler:function(e,t){!e.id||t&&e.id==t.id||this.$store.dispatch("groups/getMembers",this.group)}}},setup:function(){var e=Object(q["a"])({field:"person.last_name",desc:!1}),t=e.sort,o=e.filter;return{sort:t,filter:o}},methods:{toggleFilter:function(){this.showFilter=!this.showFilter},matchesFilters:function(e){return!(this.filters.keyword&&!e.matchesKeyword(this.filters.keyword))&&(!(this.filters.roleId&&!e.hasRole(this.filters.roleId))&&((!this.filters.needsCoi||!e.coiUpToDate())&&(!this.filters.needsTraining||!e.trainingComplete())))},toggleItemDetails:function(e){e.showDetails=!e.showDetails},editMember:function(e){this.$router.push(this.append(this.$route.path,"members/".concat(e.id)))},confirmRetireMember:function(e){this.showConfirmRetire=!0,this.selectedMember=e},retireMember:function(){var e=this;return Object(S["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,e.$store.dispatch("groups/memberRetire",{uuid:e.group.uuid,memberId:e.selectedMember.id,startDate:e.selectedMember.start_date,endDate:(new Date).toISOString()});case 3:e.cancelRetire(),t.next=9;break;case 6:t.prev=6,t.t0=t["catch"](0),console.error(t.t0);case 9:case"end":return t.stop()}}),t,null,[[0,6]])})))()},cancelRetire:function(){this.selectedMember=null,this.showConfirmRetire=!1},confirmRemoveMember:function(e){this.showConfirmRemove=!0,this.selectedMember=e},removeMember:function(){var e=this;return Object(S["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,e.$store.dispatch("groups/memberRemove",{uuid:e.group.uuid,memberId:e.selectedMember.id,startDate:e.selectedMember.start_date,endDate:(new Date).toISOString()});case 3:e.cancelRemove(),t.next=9;break;case 6:t.prev=6,t.t0=t["catch"](0),console.error(t.t0);case 9:case"end":return t.stop()}}),t,null,[[0,6]])})))()},cancelRemove:function(){this.selectedMember=null,this.showConfirmRemove=!1},goToMember:function(e){this.$router.push({name:"PersonDetail",params:{uuid:e.person.uuid}})},hasAnyMemberPermission:function(){var e=this.hasAnyPermission([["members-update",this.group],["members-retire",this.group],["members-remove",this.group],"groups-manage"]);return e},getCoiDateStyle:function(e){return null===e.coi_last_completed?"text-red-700":"text-yellow-500"},viewCoi:function(e){var t=this;return Object(S["a"])(regeneratorRuntime.mark((function o(){return regeneratorRuntime.wrap((function(o){while(1)switch(o.prev=o.next){case 0:return console.log("viewCoi",e),t.showCoiDetail=!0,o.next=4,I["a"].get("/api/cois/".concat(e)).then((function(e){return e.data}));case 4:t.coi=o.sent;case 5:case"end":return o.stop()}}),o)})))()},downloadCoiReport:function(){var e="/report/".concat(this.group.expert_panel.coi_code);window.location=e}}};o("0241");const ge=de()(ve,[["render",M]]);t["a"]=ge},"1d7f":function(e,t,o){"use strict";var r=o("7a23"),n={class:"mb-4"},c=Object(r["createElementVNode"])("p",{class:"text-sm mb-0"},"For all variants approved by either of the processes described above, a summary of approved variants should be sent to ensure that any members absent from a call have an opportunity to review each variant. The summary should be emailed to the full VCEP after the call and should summarize decisions that were made and invite feedback within a week.",-1),l={key:1};function a(e,t,o,a,i,s){var u=Object(r["resolveComponent"])("input-row");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createElementVNode"])("div",n,[Object(r["createVNode"])(u,{modelValue:s.group.expert_panel.meeting_frequency,"onUpdate:modelValue":[t[0]||(t[0]=function(e){return s.group.expert_panel.meeting_frequency=e}),t[1]||(t[1]=function(t){return e.$emit("update")})],label:"Meeting/call frequency",errors:o.errors.meeting_frequency,placeholder:"Once per week","label-width-class":"w-44",disabled:!s.canEdit},null,8,["modelValue","errors","disabled"]),Object(r["createVNode"])(u,{modelValue:s.group.expert_panel.curation_review_protocol_id,"onUpdate:modelValue":[t[2]||(t[2]=function(e){return s.group.expert_panel.curation_review_protocol_id=e}),t[3]||(t[3]=function(t){return e.$emit("update")})],options:[{value:1,label:"Process #1: Biocurator review followed by VCEP discussion"},{value:2,label:"Process #2: Paired biocurator/expert review followed by expedited VCEP approval"}],type:"radio-group",errors:o.errors.curation_review_protocol_id,disabeld:!s.canEdit,label:"VCEP Standardized Review Process",vertical:""},null,8,["modelValue","errors","disabeld"])]),c,s.canEdit?(Object(r["openBlock"])(),Object(r["createBlock"])(u,{key:0,modelValue:s.group.expert_panel.curation_review_process_notes,"onUpdate:modelValue":[t[4]||(t[4]=function(e){return s.group.expert_panel.curation_review_process_notes=e}),t[5]||(t[5]=function(t){return e.$emit("update")})],type:"large-text",label:"Curation and Review Process Notes",vertical:!0,"label-class":"font-bold"},null,8,["modelValue"])):(Object(r["openBlock"])(),Object(r["createElementBlock"])("blockquote",l,Object(r["toDisplayString"])(s.group.expert_panel.curation_review_process_notes),1))])}var i={name:"VcepOngoingPlansForm",props:{errors:{type:Object,required:!1,default:function(){return{}}},editing:{type:Boolean,required:!1,default:!0},readonly:{type:Boolean,required:!1,default:!1}},emits:["update"],computed:{group:{get:function(){return this.$store.getters["groups/currentItemOrNew"]},set:function(e){this.$store.commit("groups/addItem",e)}},canEdit:function(){return this.hasAnyPermission(["ep-applications-manage",["application-edit",this.group]])&&!this.readonly}}},s=o("6b0d"),u=o.n(s);const d=u()(i,[["render",a]]);t["a"]=d},5257:function(e,t,o){"use strict";var r=o("5530"),n=(o("caad"),o("2532"),o("b64b"),o("ac1f"),o("5319"),o("4de4"),o("d3b7"),o("6c02")),c=o("7a23");t["a"]=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null,t=Object(n["d"])(),o=Object(n["c"])();e||(console.log('Warning: defaultSort is deprecated.  Please provide a sort object: {field: "fieldname", desc: boolean}'),e=e||{field:"name",desc:!1});var l=Object(c["computed"])({immediate:!0,get:function(){return Object.keys(o.query).includes("sort-field")?{field:o.query["sort-field"],desc:Boolean(parseInt(o.query["sort-desc"]))}:e},set:function(e){var n={"sort-field":e.field,"sort-desc":e.desc?1:0},c=Object(r["a"])(Object(r["a"])({},o.query),n);t.replace({path:o.path,query:c})}}),a=Object(c["computed"])({set:function(e){var n=o.query,c=o.path,l=Object(r["a"])({},n);e?l=Object(r["a"])(Object(r["a"])({},n),{filter:e}):delete l.filter,t.replace({path:c,query:l})},get:function(){return o.query.filter},immediate:!0});return{sort:l,filter:a}}},6786:function(e,t,o){"use strict";o("04e9")},"6ca3":function(e,t,o){"use strict";var r=o("7a23"),n=Object(r["createElementVNode"])("p",null,"Three examples of ClinGen-approved curation and review protocols are below (additional details may be requested from the CDWG Oversight Committee). Check or describe the curation and review protocol that this Expert Panel will use.",-1),c={class:"mb-4"},l={class:"mt-2"},a=["disabled"],i=Object(r["createElementVNode"])("div",null,"Single biocurator curation with comprehensive GCEP review (presentation of all data on calls with GCEP votes). Note: definitive genes may be expedited with brief summaries.",-1),s={class:"mt-2 items-top"},u=["disabled"],d=Object(r["createElementVNode"])("p",null,"Paired review (biocurator & domain expert) with expedited GCEP review. Expert works closely with a curator on the initial summation of the information for expedited GCEP review (brief summary on a call with GCEP voting and/or electronic voting by GCEP). Definitive genes can move directly from biocurator to expedited GCEP review.",-1),b={class:"mt-2"},m=["disabled"],p=Object(r["createElementVNode"])("p",null,"Dual biocurator review with expedited GCEP review for concordant genes and full review for discordant genes.",-1),f={class:"flex space-x-2 items-start mt-3"},O=["disabled"],j=Object(r["createElementVNode"])("p",null,"Other",-1);function h(e,t,o,h,v,g){var w=Object(r["resolveComponent"])("input-row");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[n,Object(r["createElementVNode"])("div",c,[Object(r["createVNode"])(w,{label:"",errors:o.errors.curation_review_protocol_id,vertical:""},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",null,[Object(r["createElementVNode"])("label",l,[Object(r["withDirectives"])(Object(r["createElementVNode"])("input",{type:"radio","onUpdate:modelValue":t[0]||(t[0]=function(e){return g.group.expert_panel.curation_review_protocol_id=e}),value:"1",disabled:!g.canEdit,onInput:t[1]||(t[1]=function(t){return e.$emit("update")})},null,40,a),[[r["vModelRadio"],g.group.expert_panel.curation_review_protocol_id]]),i]),Object(r["createElementVNode"])("label",s,[Object(r["withDirectives"])(Object(r["createElementVNode"])("input",{type:"radio","onUpdate:modelValue":t[2]||(t[2]=function(e){return g.group.expert_panel.curation_review_protocol_id=e}),value:"2",disabled:!g.canEdit,onInput:t[3]||(t[3]=function(t){return e.$emit("update")})},null,40,u),[[r["vModelRadio"],g.group.expert_panel.curation_review_protocol_id]]),d]),Object(r["createElementVNode"])("label",b,[Object(r["withDirectives"])(Object(r["createElementVNode"])("input",{type:"radio","onUpdate:modelValue":t[4]||(t[4]=function(e){return g.group.expert_panel.curation_review_protocol_id=e}),value:"3",disabled:!g.canEdit,onInput:t[5]||(t[5]=function(t){return e.$emit("update")})},null,40,m),[[r["vModelRadio"],g.group.expert_panel.curation_review_protocol_id]]),p]),Object(r["createElementVNode"])("div",f,[Object(r["createElementVNode"])("label",null,[Object(r["withDirectives"])(Object(r["createElementVNode"])("input",{type:"radio","onUpdate:modelValue":t[6]||(t[6]=function(e){return g.group.expert_panel.curation_review_protocol_id=e}),value:"100",disabled:!g.canEdit,onInput:t[7]||(t[7]=function(t){return e.$emit("update")})},null,40,O),[[r["vModelRadio"],g.group.expert_panel.curation_review_protocol_id]]),j]),Object(r["createVNode"])(r["Transition"],{name:"slide-fade-down"},{default:Object(r["withCtx"])((function(){return[100==g.group.expert_panel.curation_review_protocol_id?(Object(r["openBlock"])(),Object(r["createBlock"])(w,{key:0,class:"flex-1 mt-0","label-width-class":"w-0",modelValue:g.group.expert_panel.curation_review_protocol_other,"onUpdate:modelValue":[t[8]||(t[8]=function(e){return g.group.expert_panel.curation_review_protocol_other=e}),t[9]||(t[9]=function(t){return e.$emit("update")})],errors:o.errors.curation_review_protocol_other,type:"large-text"},null,8,["modelValue","errors"])):Object(r["createCommentVNode"])("",!0)]})),_:1})])])]})),_:1},8,["errors"])])])}var v={name:"GcepOngoingPlansForm",props:{errors:{type:Object,required:!1,default:function(){return{}}},readonly:{type:Boolean,required:!1,default:!1}},computed:{group:{get:function(){return this.$store.getters["groups/currentItemOrNew"]},set:function(e){this.$store.commit("groups/addItem",e)}},canEdit:function(){return this.hasAnyPermission(["ep-applications-manage",["application-edit",this.group]])&&!this.readonly}}},g=o("6b0d"),w=o.n(g);const _=w()(v,[["render",h]]);t["a"]=_},"8c98":function(e,t,o){"use strict";o("99af"),o("b0c0");var r=o("7a23"),n={key:0},c={class:"block-title"},l=Object(r["createTextVNode"])(" COI response for "),a={key:0},i={class:"text-sm response-data"},s={class:"flex-0"},u=Object(r["createElementVNode"])("p",{class:"mb-2"},"This is a legacy response.",-1),d={key:1,class:"response-data"};function b(e,t,o,b,m,p){var f=Object(r["resolveComponent"])("dictionary-row");return p.response?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",n,[Object(r["createElementVNode"])("h2",c,[l,o.coi.data.first_name?(Object(r["openBlock"])(),Object(r["createElementBlock"])("span",a,Object(r["toDisplayString"])(e.titleCase("".concat(o.coi.data.first_name," ").concat(o.coi.data.last_name)))+" in ",1)):Object(r["createCommentVNode"])("",!0),Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(o.group.name),1)]),Object(r["createElementVNode"])("div",i,[Object(r["createVNode"])(f,{label:"Name","label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(o.coi.data.first_name)+" "+Object(r["toDisplayString"])(o.coi.data.last_name),1)]})),_:1}),Object(r["createVNode"])(f,{label:"Email","label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(o.coi.data.email),1)]})),_:1}),p.response.document_uuid?(Object(r["openBlock"])(),Object(r["createBlock"])(f,{key:0,label:"COI File","label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",s,[u,Object(r["createElementVNode"])("button",{class:"btn btn-xs",onClick:t[0]||(t[0]=function(e){return p.downloadDocument(p.response.download_url.response)})}," Download the COI. ")])]})),_:1})):Object(r["createCommentVNode"])("",!0),p.response.document_uuid?Object(r["createCommentVNode"])("",!0):(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",d,[Object(r["createVNode"])(f,{label:p.response.work_fee_lab.question,vertical:!0,"label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(p.getQuestionValue(p.response.work_fee_lab.response)),1)]})),_:1},8,["label"]),Object(r["createVNode"])(f,{label:p.response.contributions_to_gd_in_ep.question,vertical:!0,"label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(p.getQuestionValue(p.response.contributions_to_gd_in_ep.response))+" ",1),1==p.response.contributions_to_gd_in_ep.response?(Object(r["openBlock"])(),Object(r["createBlock"])(f,{key:0,label:p.response.contributions_to_genes.question,vertical:!0,class:"pb-1 mb-1 ml-4","label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(p.getQuestionValue(p.response.contributions_to_genes.response)),1)]})),_:1},8,["label"])):Object(r["createCommentVNode"])("",!0)]})),_:1},8,["label"]),Object(r["createVNode"])(f,{label:p.response.independent_efforts.question,vertical:!0,"label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(p.getQuestionValue(p.response.independent_efforts.response))+" ",1),[1,2].indexOf(p.response.independent_efforts.response)>-1?(Object(r["openBlock"])(),Object(r["createBlock"])(f,{key:0,label:p.response.independent_efforts_details.question,vertical:!0,class:"pb-1 mb-1 ml-4 border-none","label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(p.getQuestionValue(p.response.independent_efforts_details.response)),1)]})),_:1},8,["label"])):Object(r["createCommentVNode"])("",!0)]})),_:1},8,["label"]),Object(r["createVNode"])(f,{label:p.response.coi.question,vertical:!0,class:"pb-1 mb-1","label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(p.getQuestionValue(p.response.coi.response))+" ",1),[1,2].indexOf(p.response.coi.response)>-1?(Object(r["openBlock"])(),Object(r["createBlock"])(f,{key:0,label:p.response.coi_details.question,vertical:!0,class:"pb-1 mb-1 ml-4","label-class":"font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(p.getQuestionValue(p.response.coi_details.response)),1)]})),_:1},8,["label"])):Object(r["createCommentVNode"])("",!0)]})),_:1},8,["label"])]))])])):Object(r["createCommentVNode"])("",!0)}var m={props:{coi:{type:Object,required:!0},group:{type:Object,required:!0}},data:function(){return{}},computed:{isLegacy:function(){return!1},response:function(){return this.coi.response_document}},methods:{getQuestionValue:function(e){return 1===e?"Yes":0===e?"No":2===e?"Unsure":e},downloadDocument:function(e){window.location=e}}},p=(o("6786"),o("6b0d")),f=o.n(p);const O=f()(m,[["render",b]]);t["a"]=O},ca76:function(e,t,o){}}]);
//# sourceMappingURL=chunk-187bb237.27c39274.js.map