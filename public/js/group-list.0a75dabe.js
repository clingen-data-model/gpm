(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["group-list"],{"268c":function(e,t,r){"use strict";r("b0c0");var n=r("7a23"),o=Object(n["createElementVNode"])("option",{value:null},"Select…",-1),a=["value"],u={key:0},c={key:0},i=Object(n["createTextVNode"])(" Affiliation ID "),l=Object(n["createTextVNode"])("admin-only"),s={key:0},p={key:1,class:"text-gray-400"},d={key:1},b={key:2},m=Object(n["createTextVNode"])(" Status: "),f=Object(n["createTextVNode"])("admin-only"),g=Object(n["createElementVNode"])("option",{value:null},null,-1),O=["value"],j=Object(n["createTextVNode"])(" Parent group: "),h=Object(n["createTextVNode"])("admin-only"),_=Object(n["createElementVNode"])("option",{value:null},"Select...",-1),v=Object(n["createElementVNode"])("option",{value:0},"None",-1),k=["value"];function w(e,t,r,w,y,x){var C=Object(n["resolveComponent"])("input-row"),V=Object(n["resolveComponent"])("dictionary-row"),N=Object(n["resolveComponent"])("note");return Object(n["openBlock"])(),Object(n["createElementBlock"])("div",null,[x.canSetType?(Object(n["openBlock"])(),Object(n["createBlock"])(C,{key:0,label:"Type"},{default:Object(n["withCtx"])((function(){return[Object(n["withDirectives"])(Object(n["createElementVNode"])("select",{"onUpdate:modelValue":t[0]||(t[0]=function(e){return x.group.group_type_id=e}),class:"w-full"},[o,(Object(n["openBlock"])(!0),Object(n["createElementBlock"])(n["Fragment"],null,Object(n["renderList"])(y.groupTypes,(function(e){return Object(n["openBlock"])(),Object(n["createElementBlock"])("option",{key:e.id,value:e.id},Object(n["toDisplayString"])(e.fullname),9,a)})),128))],512),[[n["vModelSelect"],x.group.group_type_id]])]})),_:1})):(Object(n["openBlock"])(),Object(n["createBlock"])(V,{key:1,label:"Type"},{default:Object(n["withCtx"])((function(){return[Object(n["createTextVNode"])(Object(n["toDisplayString"])(x.typeDisplayName),1)]})),_:1})),Object(n["createVNode"])(n["Transition"],{name:"slide-fade-down",mode:"out-in"},{default:Object(n["withCtx"])((function(){return[x.group.group_type_id>2&&x.group.expert_panel?(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",u,[Object(n["createVNode"])(C,{label:"Long Base Name",modelValue:x.group.expert_panel.long_base_name,"onUpdate:modelValue":t[1]||(t[1]=function(e){return x.group.expert_panel.long_base_name=e}),placeholder:"Long base name",errors:w.errors.long_base_name,"input-class":"w-full"},null,8,["modelValue","errors"]),Object(n["createVNode"])(C,{label:"Short Base Name",modelValue:x.group.expert_panel.short_base_name,"onUpdate:modelValue":t[2]||(t[2]=function(e){return x.group.expert_panel.short_base_name=e}),placeholder:"Short base name",errors:w.errors.short_base_name,"input-class":"w-full"},null,8,["modelValue","errors"]),e.hasAnyPermission(["groups-manage"])?(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",c,[Object(n["createVNode"])(C,{label:"Affiliation ID",modelValue:x.group.expert_panel.affiliation_id,"onUpdate:modelValue":t[3]||(t[3]=function(e){return x.group.expert_panel.affiliation_id=e}),placeholder:x.affiliationIdPlaceholder,errors:w.errors.affiliation_id,"input-class":"w-full"},{label:Object(n["withCtx"])((function(){return[i,Object(n["createVNode"])(N,null,{default:Object(n["withCtx"])((function(){return[l]})),_:1})]})),_:1},8,["modelValue","placeholder","errors"])])):(Object(n["openBlock"])(),Object(n["createBlock"])(V,{key:1,label:"Affiliation ID"},{default:Object(n["withCtx"])((function(){return[x.group.expert_panel.affiliation_id?(Object(n["openBlock"])(),Object(n["createElementBlock"])("span",s,Object(n["toDisplayString"])(x.group.expert_panel.affiliation_id),1)):(Object(n["openBlock"])(),Object(n["createElementBlock"])("span",p,Object(n["toDisplayString"])("Not yet assigend")))]})),_:1}))])):(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",d,[Object(n["createVNode"])(C,{modelValue:x.group.name,"onUpdate:modelValue":t[4]||(t[4]=function(e){return x.group.name=e}),placeholder:"Name",label:"Name","input-class":"w-full",errors:w.errors.name},null,8,["modelValue","errors"])]))]})),_:1}),e.hasPermission("groups-manage")?(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",b,[Object(n["createVNode"])(C,{errors:w.errors.group_status_id},{label:Object(n["withCtx"])((function(){return[m,Object(n["createVNode"])(N,null,{default:Object(n["withCtx"])((function(){return[f]})),_:1})]})),default:Object(n["withCtx"])((function(){return[Object(n["withDirectives"])(Object(n["createElementVNode"])("select",{"onUpdate:modelValue":t[5]||(t[5]=function(e){return x.group.group_status_id=e}),class:"w-full"},[g,(Object(n["openBlock"])(!0),Object(n["createElementBlock"])(n["Fragment"],null,Object(n["renderList"])(y.groupStatuses,(function(t){return Object(n["openBlock"])(),Object(n["createElementBlock"])("option",{key:t.id,value:t.id},Object(n["toDisplayString"])(e.titleCase(t.name)),9,O)})),128))],512),[[n["vModelSelect"],x.group.group_status_id]])]})),_:1},8,["errors"]),Object(n["createVNode"])(C,{errors:w.errors.parent_id},{label:Object(n["withCtx"])((function(){return[j,Object(n["createVNode"])(N,null,{default:Object(n["withCtx"])((function(){return[h]})),_:1})]})),default:Object(n["withCtx"])((function(){return[Object(n["withDirectives"])(Object(n["createElementVNode"])("select",{"onUpdate:modelValue":t[6]||(t[6]=function(e){return x.group.parent_id=e}),class:"w-full"},[_,v,(Object(n["openBlock"])(!0),Object(n["createElementBlock"])(n["Fragment"],null,Object(n["renderList"])(y.parents,(function(e){return Object(n["openBlock"])(),Object(n["createElementBlock"])("option",{key:e.id,value:e.id},Object(n["toDisplayString"])(e.displayName),9,k)})),128))],512),[[n["vModelSelect"],x.group.parent_id]])]})),_:1},8,["errors"])])):Object(n["createCommentVNode"])("",!0)])}var y=r("1da1"),x=(r("96cf"),r("d3b7"),r("3ca3"),r("ddb0"),r("d81d"),r("4de4"),r("db04")),C=r("ae23"),V=r("4a91"),N=r("a75a"),B={name:"GroupForm",emits:["canceled","saved"],data:function(){return{groupTypes:[{id:1,fullname:"Working Group"},{id:2,fullname:"Clinical Domain Working Group"},{id:3,fullname:"GCEP"},{id:4,fullname:"VCEP"}],groupStatuses:V.groups.statuses,newGroup:new C["a"],parents:[]}},computed:{group:{get:function(){var e=this.$store.getters["groups/currentItem"];return e||this.newGroup},set:function(e){try{this.$store.commit("groups/addItem",e)}catch(t){this.newGroup=e}}},canSetType:function(){return this.hasPermission("groups-manage")&&!this.group.id},typeDisplayName:function(){return this.group.type?this.group.type.name?this.group.type.id<3?this.group.type.name.toUpperCase():this.group.expert_panel.type.name.toUpperCase():null:"🐇🥚"},affiliationIdPlaceholder:function(){return 5e4},cdwgs:function(){return this.$store.getters["cdwgs/all"]},namesDirty:function(){return this.group.expert_panel.isDirty("long_base_name")||this.group.expert_panel.isDirty("short_base_name")},affiliationIdDirty:function(){return this.group.expert_panel.isDirty("affiliation_id")}},methods:{save:function(){var e=this;return Object(y["a"])(regeneratorRuntime.mark((function t(){var r;return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:if(t.prev=0,e.resetErrors(),!e.group.id){t.next=9;break}return t.next=5,e.updateGroup();case 5:return e.$emit("saved"),e.$store.dispatch("groups/find",e.group.uuid),e.$store.commit("pushSuccess","Group info updated."),t.abrupt("return");case 9:return t.next=11,e.createGroup().then((function(e){return e.data.data}));case 11:r=t.sent,e.$emit("saved"),e.$store.commit("pushSuccess","Group created."),e.$router.push({name:"AddMember",params:{uuid:r.uuid}}),t.next=20;break;case 17:t.prev=17,t.t0=t["catch"](0),Object(x["c"])(t.t0)&&(e.errors=t.t0.response.data.errors);case 20:case"end":return t.stop()}}),t,null,[[0,17]])})))()},createGroup:function(){var e=this.group.attributes,t=e.name,r=e.parent_id,n=e.group_type_id,o=e.group_status_id;return null===t&&this.group.expert_panel&&(t=this.group.expert_panel.long_base_name),this.$store.dispatch("groups/create",{name:t,parent_id:r,group_type_id:n,group_status_id:o})},updateGroup:function(){var e=[];return e.push(this.saveGroupData()),this.group.expert_panel&&e.push(this.saveEpData()),Promise.all(e)},saveGroupData:function(){var e=[];return this.group.isDirty("parent_id")&&e.push(this.saveParent()),this.group.isDirty("name")&&e.push(this.saveName()),this.group.isDirty("group_status_id")&&e.push(this.saveStatus()),Promise.all(e)},saveEpData:function(){var e=this;return Object(y["a"])(regeneratorRuntime.mark((function t(){var r,n,o,a;return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return r=[],e.namesDirty&&(n=e.group.expert_panel,o=n.long_base_name,a=n.short_base_name,r.push(e.submitFormData({method:"put",url:"/api/groups/".concat(e.group.uuid,"/expert-panel/name"),data:{long_base_name:o,short_base_name:a}}))),e.affiliationIdDirty&&r.push(e.submitFormData({method:"put",url:"/api/groups/".concat(e.group.uuid,"/expert-panel/affiliation-id"),data:{affiliation_id:e.affiliationId}})),t.next=5,Promise.all(r);case 5:return t.abrupt("return",t.sent);case 6:case"end":return t.stop()}}),t)})))()},isDirty:function(e){return this.group[e]!=this.group[e]},saveParent:function(){return this.submitFormData({method:"put",url:"/api/groups/".concat(this.group.uuid,"/parent"),data:{parent_id:this.group.parent_id}})},saveName:function(){return this.submitFormData({method:"put",url:"/api/groups/".concat(this.group.uuid,"/name"),data:{name:this.group.name}})},saveStatus:function(){return this.submitFormData({method:"put",url:"/api/groups/".concat(this.group.uuid,"/status"),data:{status_id:this.group.group_status_id}})},resetData:function(){this.group.uuid&&this.$store.dispatch("groups/find",this.group.uuid)},cancel:function(){this.group.uuid&&this.resetData(),this.$emit("canceled")},getParentOptions:function(){var e=this;return Object(y["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,x["a"].get("/api/groups").then((function(t){return t.data.filter((function(t){return t.id!=e.group.id})).map((function(e){return new C["a"](e)}))}));case 2:e.parents=t.sent;case 3:case"end":return t.stop()}}),t)})))()}},beforeMount:function(){this.getParentOptions(),this.$store.dispatch("cdwgs/getAll")},setup:function(e,t){var r=Object(N["a"])(e,t),n=r.errors,o=r.submitFormData,a=r.resetErrors;return{errors:n,submitFormData:o,resetErrors:a}}};B.render=w;t["a"]=B},"7ca5":function(e,t,r){"use strict";r.r(t);r("4de4"),r("4e82"),r("b0c0");var n=r("7a23"),o={class:"flex justify-between items-center"},a=Object(n["createTextVNode"])(" Groups "),u={key:0},c={key:0};function i(e,t,r,i,l,s){var p=Object(n["resolveComponent"])("badge"),d=Object(n["resolveComponent"])("router-link"),b=Object(n["resolveComponent"])("data-table"),m=Object(n["resolveComponent"])("tab-item"),f=Object(n["resolveComponent"])("tabs-container"),g=Object(n["resolveComponent"])("group-form"),O=Object(n["resolveComponent"])("submission-wrapper"),j=Object(n["resolveComponent"])("modal-dialog"),h=Object(n["resolveDirective"])("remaining-height");return Object(n["openBlock"])(),Object(n["createElementBlock"])("div",null,[Object(n["createElementVNode"])("h1",o,[a,e.hasPermission("groups-manage")?(Object(n["openBlock"])(),Object(n["createElementBlock"])("button",{key:0,class:"btn btn-xs",onClick:t[0]||(t[0]=function(){return s.startCreateGroup&&s.startCreateGroup.apply(s,arguments)})},"Create a group")):Object(n["createCommentVNode"])("",!0)]),Object(n["createVNode"])(f,null,{default:Object(n["withCtx"])((function(){return[(Object(n["openBlock"])(!0),Object(n["createElementBlock"])(n["Fragment"],null,Object(n["renderList"])(l.tabDefinitions,(function(e){return Object(n["openBlock"])(),Object(n["createBlock"])(m,{label:e.label,key:e.label},{default:Object(n["withCtx"])((function(){return[Object(n["withDirectives"])(Object(n["createVNode"])(b,{data:i.filteredGroups.filter(e.filter),fields:l.fields,sort:l.sort,"onUpdate:sort":t[2]||(t[2]=function(e){return l.sort=e}),"row-click-handler":i.goToGroup,"row-class":"cursor-pointer active:bg-blue-100"},{"cell-displayStatus":Object(n["withCtx"])((function(e){var t=e.item;return[Object(n["createVNode"])(p,{class:"text-xs",color:t.statusColor},{default:Object(n["withCtx"])((function(){return[Object(n["createTextVNode"])(Object(n["toDisplayString"])(t.displayStatus),1)]})),_:2},1032,["color"])]})),"cell-coordinators":Object(n["withCtx"])((function(e){var r=e.value;return[0==r.length?(Object(n["openBlock"])(),Object(n["createElementBlock"])("div",u)):Object(n["createCommentVNode"])("",!0),(Object(n["openBlock"])(!0),Object(n["createElementBlock"])(n["Fragment"],null,Object(n["renderList"])(r,(function(e,r){return Object(n["openBlock"])(),Object(n["createElementBlock"])("span",{key:e.id},[r>0?(Object(n["openBlock"])(),Object(n["createElementBlock"])("span",c,", ")):Object(n["createCommentVNode"])("",!0),Object(n["createVNode"])(d,{to:{name:"PersonDetail",params:{uuid:e.person.uuid}},class:"link",onClick:t[1]||(t[1]=Object(n["withModifiers"])((function(){}),["stop"]))},{default:Object(n["withCtx"])((function(){return[Object(n["createTextVNode"])(Object(n["toDisplayString"])(e.person.name),1)]})),_:2},1032,["to"])])})),128))]})),_:2},1032,["data","fields","sort","row-click-handler"]),[[h]])]})),_:2},1032,["label"])})),128))]})),_:1}),Object(n["createVNode"])(j,{modelValue:l.showCreateForm,"onUpdate:modelValue":t[7]||(t[7]=function(e){return l.showCreateForm=e}),title:"Create a New Group",size:"sm"},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(O,{onSubmitted:t[5]||(t[5]=function(t){return e.$refs.groupForm.save()}),onCanceled:t[6]||(t[6]=function(t){return e.$refs.groupForm.cancel()})},{default:Object(n["withCtx"])((function(){return[Object(n["createVNode"])(g,{ref:"groupForm",onCanceled:t[3]||(t[3]=function(e){return l.showCreateForm=!1}),onSaved:t[4]||(t[4]=function(e){return l.showCreateForm=!1})},null,512)]})),_:1})]})),_:1},8,["modelValue"])])}var l=r("5502"),s=r("6c02"),p=r("268c"),d=r("feb0"),b={name:"ComponentName",components:{GroupForm:p["a"],SubmissionWrapper:d["a"]},props:{},data:function(){return{showCreateForm:!1,tabDefinitions:[{label:"VCEPs",filter:function(e){return e.isVcep()}},{label:"GCEPs",filter:function(e){return e.isGcep()}},{label:"CDWGs",filter:function(e){return e.isCdwg()}},{label:"WGs",filter:function(e){return e.isWg()}}],sort:{field:"id",desc:!1},fields:[{name:"id",label:"ID",sortable:!0},{name:"name",label:"Name",sortable:!0,resolveValue:function(e){return e.displayName}},{name:"coordinators",sortable:!1},{name:"displayStatus",sortable:!0,label:"status"}]}},methods:{startCreateGroup:function(){this.showCreateForm=!0}},setup:function(){var e=Object(l["d"])(),t=Object(s["d"])(),r=Object(n["computed"])((function(){return e.getters["groups/all"]})),o=Object(n["computed"])((function(){return r.value.filter((function(){return!0}))})),a=function(e){t.push({name:"GroupDetail",params:{uuid:e.uuid}})};return Object(n["onMounted"])((function(){e.dispatch("groups/getItems")})),{groups:r,filteredGroups:o,goToItem:a,goToGroup:a}}};b.render=i;t["default"]=b},a75a:function(e,t,r){"use strict";r.d(t,"b",(function(){return i})),r.d(t,"d",(function(){return p})),r.d(t,"c",(function(){return d}));var n=r("5530"),o=r("1da1"),a=(r("96cf"),r("7a23")),u=r("033d"),c=r("f96b"),i=Object(a["ref"])({}),l=Object(a["ref"])(!1),s=function(){l.value=!1,i.value={}},p=function(){var e=Object(o["a"])(regeneratorRuntime.mark((function e(t){var r,o,a;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return r=t.method,o=t.url,a=t.data,e.prev=1,e.next=4,Object(c["a"])({method:r,url:o,data:a}).then((function(e){return e.data.data}));case 4:return e.abrupt("return",e.sent);case 7:e.prev=7,e.t0=e["catch"](1),Object(u["a"])(e.t0)&&(i.value=Object(n["a"])(Object(n["a"])({},i),e.t0.response.data.errors));case 10:case"end":return e.stop()}}),e,null,[[1,7]])})));return function(t){return e.apply(this,arguments)}}(),d=function(){i.value={}};t["a"]=function(){return{errors:i,editing:l,hideForm:s,submitFormData:p,resetErrors:d}}}}]);
//# sourceMappingURL=group-list.0a75dabe.js.map