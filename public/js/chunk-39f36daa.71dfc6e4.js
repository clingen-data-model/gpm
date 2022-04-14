(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-39f36daa"],{"03c4":function(e,t,n){"use strict";n("768b")},"494c":function(e,t,n){},"73ff":function(e,t,n){"use strict";n.r(t);n("b0c0");var r=n("7a23"),o={class:"pb-4"},a={class:"flex space-x-4"},c={class:"flex-1"},l=Object(r["createTextVNode"])("People"),i={class:"flex justify-between items-center"},s=Object(r["createElementVNode"])("h2",null,"Groups",-1),u=Object(r["createElementVNode"])("h2",null,"Profile",-1),d={class:"flex space-x-4 items-center"},b={class:"flex space-x-4 items-center"},p=Object(r["createElementVNode"])("h2",null,"Log Entries",-1),m={class:"border my-4 p-4 bg-red-100 border-red-200 rounded"},j=Object(r["createElementVNode"])("h2",{class:"mb-4 text-red-800"}," Here be dragons. Proceed with caution. ",-1),O=Object(r["createElementVNode"])("p",null,"You are about to delete this person. All related data will also be deleted including:",-1),f=Object(r["createElementVNode"])("ul",{class:"list-disc pl-6"},[Object(r["createElementVNode"])("li",null,"User record, system roles, and system permissions (if account is activated)"),Object(r["createElementVNode"])("li",null,"Invite"),Object(r["createElementVNode"])("li",null,"Group Memberships and group roles (if any)")],-1),g=Object(r["createElementVNode"])("div",{class:"border my-4 px-2 py-1 font-bold bg-red-100 border-red-200 rounded text-red-800"}," This cannot be undone. ",-1);function h(e,t,n,h,v,y){var V=Object(r["resolveComponent"])("profile-picture"),C=Object(r["resolveComponent"])("router-link"),w=Object(r["resolveComponent"])("note"),N=Object(r["resolveComponent"])("icon-edit"),E=Object(r["resolveComponent"])("dictionary-row"),x=Object(r["resolveComponent"])("membership-list"),k=Object(r["resolveComponent"])("tab-item"),D=Object(r["resolveComponent"])("person-profile"),_=Object(r["resolveComponent"])("coi-list"),B=Object(r["resolveComponent"])("refresh-button"),M=Object(r["resolveComponent"])("person-mail-log"),L=Object(r["resolveComponent"])("activity-log"),S=Object(r["resolveComponent"])("tabs-container"),R=Object(r["resolveComponent"])("router-view"),P=Object(r["resolveComponent"])("modal-dialog"),T=Object(r["resolveComponent"])("button-row"),U=Object(r["resolveComponent"])("person-merge-form");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createElementVNode"])("header",o,[Object(r["createElementVNode"])("div",a,[Object(r["createVNode"])(V,{person:e.person,style:{width:"155px"},class:"rounded-lg"},null,8,["person"]),Object(r["createElementVNode"])("div",c,[Object(r["createVNode"])(C,{class:"note",to:"/people"},{default:Object(r["withCtx"])((function(){return[l]})),_:1}),Object(r["createElementVNode"])("h1",i,[Object(r["createElementVNode"])("div",null,[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.person.name)+" ",1),Object(r["createVNode"])(w,null,{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])("ID: "+Object(r["toDisplayString"])(e.person.id),1)]})),_:1})]),e.hasPermission("people-manage")||e.userIsPerson(e.person)||e.coordinatesPerson(e.person)?(Object(r["openBlock"])(),Object(r["createBlock"])(C,{key:0,to:"/people/".concat(n.uuid,"/edit"),class:"btn btn-xs flex-grow-0"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(N,{width:"16",heigh:"16"})]})),_:1},8,["to"])):Object(r["createCommentVNode"])("",!0)]),Object(r["createVNode"])(E,{label:"Email","label-class":"w-24 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.person.email),1)]})),_:1}),Object(r["createVNode"])(E,{label:"Institution","label-class":"w-24 font-bold"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.person.institution?e.person.institution.name:null),1)]})),_:1})])])]),Object(r["createVNode"])(S,null,{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(k,{label:"groups"},{default:Object(r["withCtx"])((function(){return[s,Object(r["createVNode"])(x,{person:e.person},null,8,["person"])]})),_:1}),Object(r["createVNode"])(k,{label:"Info"},{default:Object(r["withCtx"])((function(){return[u,Object(r["createVNode"])(D,{person:e.person},null,8,["person"])]})),_:1}),Object(r["createVNode"])(k,{label:"Conflict of Interest"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(_,{person:e.person},null,8,["person"])]})),_:1}),Object(r["createVNode"])(k,{label:"Email Log",visible:e.hasPermission("people-manage")||e.userIsPerson(e.person)||e.coordinatesPerson(e.person)},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("header",d,[Object(r["createElementVNode"])("h2",null,"Mail sent to "+Object(r["toDisplayString"])(e.person.first_name),1),Object(r["createVNode"])(B,{loading:v.mailLoading,onClick:y.getMailLog},null,8,["loading","onClick"])]),Object(r["createVNode"])(M,{person:e.person,mail:y.sortedMailLog},null,8,["person","mail"])]})),_:1},8,["visible"]),Object(r["createVNode"])(k,{label:"Log",visible:e.hasPermission("people-manage")||e.coordinatesPerson(e.person)},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",b,[p,Object(r["createVNode"])(B,{loading:v.logsLoading,onClick:y.getLogEntries},null,8,["loading","onClick"])]),Object(r["createVNode"])(L,{"log-entries":h.logEntries,"api-url":"/api/people/".concat(e.person.uuid,"/activity-logs"),"log-updated":y.getLogEntries},null,8,["log-entries","api-url","log-updated"])]})),_:1},8,["visible"]),Object(r["createVNode"])(k,{label:"Admin",visible:e.hasPermission("people-manage")},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("section",m,[j,Object(r["createElementVNode"])("p",null,[Object(r["createElementVNode"])("button",{class:"btn btn red",onClick:t[0]||(t[0]=function(){return y.initMerge&&y.initMerge.apply(y,arguments)})},"Merge Person into another")]),Object(r["createElementVNode"])("p",null,[Object(r["createElementVNode"])("button",{class:"btn btn red",onClick:t[1]||(t[1]=function(){return y.initDelete&&y.initDelete.apply(y,arguments)})},"Delete Person")])])]})),_:1},8,["visible"])]})),_:1}),(Object(r["openBlock"])(),Object(r["createBlock"])(r["Teleport"],{to:"body"},[Object(r["createVNode"])(P,{modelValue:y.showModal,"onUpdate:modelValue":t[2]||(t[2]=function(e){return y.showModal=e}),title:e.$route.meta.title},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(R,{name:"modal"})]})),_:1},8,["modelValue","title"]),Object(r["createVNode"])(P,{title:"You are about to delete ".concat(e.person.name),modelValue:v.showDeleteConfirmation,"onUpdate:modelValue":t[3]||(t[3]=function(e){return v.showDeleteConfirmation=e})},{default:Object(r["withCtx"])((function(){return[O,f,g,Object(r["createVNode"])(T,{"submit-text":"Delete ".concat(e.person.name),onSubmitted:y.commitDelete,onCanceled:y.cancelDelete,"submit-variant":"red"},null,8,["submit-text","onSubmitted","onCanceled"])]})),_:1},8,["title","modelValue"]),Object(r["createVNode"])(P,{title:"Merge ".concat(e.person.name," into another person"),modelValue:v.showMergeForm,"onUpdate:modelValue":t[4]||(t[4]=function(e){return v.showMergeForm=e})},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(U,{obsolete:e.person,onSaved:y.handleMerged,onCanceled:y.handleMergeCanceled},null,8,["obsolete","onSaved","onCanceled"])]})),_:1},8,["title","modelValue"])]))])}var v=n("2909"),y=n("5530"),V=n("1da1"),C=(n("4e82"),n("96cf"),n("5502")),w=n("e328"),N=n("831e"),E=n("b442");function x(e,t,n,o,a,c){var l=Object(r["resolveComponent"])("data-table");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createVNode"])(l,{fields:a.fields,data:c.memberships,sort:a.sort,"onUpdate:sort":t[0]||(t[0]=function(e){return a.sort=e}),rowClickHandler:c.goToGroup,"row-class":function(){return"cursor-pointer"}},null,8,["fields","data","sort","rowClickHandler","row-class"])])}n("a15b"),n("d81d");var k=n("5313"),D={name:"MembershipList",props:{person:{type:Object,required:!0}},data:function(){return{sort:{field:"group.displayName",desc:!1},fields:[{name:"group.displayName",type:String,sortable:!0,label:"Name"},{name:"group.type.name",type:String,sortable:!0,label:"Type"},{name:"roles",type:String,sortable:!1,label:"Roles",resolveValue:function(e){return e.roles.map((function(e){return e.name})).join(", ")}},{name:"permissions",type:String,sortable:!1,label:"Permissions",resolveValue:function(e){return e.permissions.map((function(e){return e.name})).join(", ")}},{name:"start_date",type:Date,sortable:!0,label:"Started"},{name:"end_date",type:Date,sortable:!0,label:"Ended"}]}},computed:{memberships:function(){return this.person.memberships?this.person.memberships.map((function(e){return e.group&&(e.group=new k["b"](e.group)),e})):[]}},methods:{goToGroup:function(e){this.$router.push({name:"GroupDetail",params:{uuid:e.group.uuid}})}}},_=n("6b0d"),B=n.n(_);const M=B()(D,[["render",x]]);var L=M,S=n("c226"),R=Object(r["createElementVNode"])("p",{class:"mb-8"},[Object(r["createTextVNode"])(" Merging peple will do the following: "),Object(r["createElementVNode"])("ol",{class:"list-decimal ml-6"},[Object(r["createElementVNode"])("li",null,"Add the authoritative person to all groups of which the obsolete person is a member."),Object(r["createElementVNode"])("li",null,"Delete the obsolete person, their memberships, their invite, and their user account (if activated).")])],-1),P=Object(r["createTextVNode"])(" Merge "),T=Object(r["createTextVNode"])("(Obsolete person that will be deleted)"),U=Object(r["createTextVNode"])("Into "),I=Object(r["createTextVNode"])("(Authoritative person)");function $(e,t,n,o,a,c){var l=Object(r["resolveComponent"])("note"),i=Object(r["resolveComponent"])("person-search-select"),s=Object(r["resolveComponent"])("input-row"),u=Object(r["resolveComponent"])("button-row");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[R,Object(r["createVNode"])(s,{label:"Merge",vertical:""},{label:Object(r["withCtx"])((function(){return[P,Object(r["createVNode"])(l,{class:"inline-block"},{default:Object(r["withCtx"])((function(){return[T]})),_:1})]})),default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(i,{modelValue:a.obsoleteCopy,"onUpdate:modelValue":t[0]||(t[0]=function(e){return a.obsoleteCopy=e}),"allow-add":!1},null,8,["modelValue"])]})),_:1}),Object(r["createVNode"])(s,{label:"Into",errors:a.errors.authority_id,vertical:""},{label:Object(r["withCtx"])((function(){return[U,Object(r["createVNode"])(l,{class:"inline"},{default:Object(r["withCtx"])((function(){return[I]})),_:1})]})),default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(i,{modelValue:a.authorityCopy,"onUpdate:modelValue":t[1]||(t[1]=function(e){return a.authorityCopy=e}),"allow-add":!1},null,8,["modelValue"])]})),_:1},8,["errors"]),Object(r["createVNode"])(u,{"submit-text":"Merge",onSubmitted:c.commitMerge,onCanceled:c.cancelMerge},null,8,["onSubmitted","onCanceled"])])}var F=n("db04"),q={name:"PersonMergeForm",props:{authority:{type:Object,required:!1},obsolete:{type:Object,default:function(){return{}}}},emits:["saved","canceled"],data:function(){return{authorityCopy:null,obsoleteCopy:null,errors:{}}},watch:{authority:{immediate:!0,handler:function(e){e&&(this.authorityCopy=Object(y["a"])({},e.attributes))}},obsolete:{immediate:!0,handler:function(e){e&&(this.obsoleteCopy=Object(y["a"])({},e.attributes))}}},methods:{commitMerge:function(){var e=this;return Object(V["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,e.errors={},t.next=4,e.$store.dispatch("people/mergePeople",{authority:e.authorityCopy,obsolete:e.obsoleteCopy});case 4:e.authorityId=null,e.obsoleteIds=null,e.$emit("saved"),t.next=14;break;case 9:if(t.prev=9,t.t0=t["catch"](0),!Object(F["b"])(t.t0)){t.next=14;break}return e.errors=t.t0.response.data.errors,t.abrupt("return");case 14:case"end":return t.stop()}}),t,null,[[0,9]])})))()},cancelMerge:function(){this.authorityId=null,this.obsoleteId=null,this.$emit("canceled")}}};const H=B()(q,[["render",$]]);var A=H,G=n("6a6b"),Y=n("ffb2"),J={key:0,class:"well"},z=Object(r["createElementVNode"])("span",{class:"text-gray-600"},"Sent at",-1),K=Object(r["createElementVNode"])("span",{class:"text-gray-600"},"To:",-1),Q={key:0},W=Object(r["createElementVNode"])("span",{class:"text-gray-600"},"CC:",-1),X={key:1},Z=Object(r["createElementVNode"])("span",{class:"text-gray-600"},"BCC:",-1),ee={class:"mt-1"},te=Object(r["createElementVNode"])("span",{class:"text-gray-600"},"Subject:",-1),ne=Object(r["createElementVNode"])("hr",null,null,-1),re=["innerHTML"],oe=Object(r["createElementVNode"])("hr",null,null,-1),ae=["onClick"];function ce(e,t,n,o,a,c){var l=Object(r["resolveComponent"])("custom-email-form"),i=Object(r["resolveComponent"])("modal-dialog");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[0==n.mail.length?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",J,Object(r["toDisplayString"])(n.person.first_name)+" has not received any mail via the GPM. ",1)):Object(r["createCommentVNode"])("",!0),(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(n.mail,(function(t){return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",{class:"w-3/4 my-4 p-4 border",key:t.id},[z,Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(e.formatDate(t.created_at))+" ",1),Object(r["createElementVNode"])("div",null,[K,Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(t.to.map((function(e){return e.address})).join(", ")),1)]),t.cc?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",Q,[W,Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(t.cc.map((function(e){return e.address})).join(", ")),1)])):Object(r["createCommentVNode"])("",!0),t.bcc?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",X,[Z,Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(t.bcc.map((function(e){return e.address})).join(", ")),1)])):Object(r["createCommentVNode"])("",!0),Object(r["createElementVNode"])("div",ee,[te,Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(t.subject),1)]),ne,Object(r["createElementVNode"])("div",{innerHTML:t.body},null,8,re),oe,e.hasPermission("people-manage")||e.coordinatesPerson(n.person)?(Object(r["openBlock"])(),Object(r["createElementBlock"])("button",{key:2,onClick:Object(r["withModifiers"])((function(e){return c.initResend(t)}),["stop"]),class:"btn btn-xs"}," Resend ",8,ae)):Object(r["createCommentVNode"])("",!0)])})),128)),(Object(r["openBlock"])(),Object(r["createBlock"])(r["Teleport"],{to:"body"},[Object(r["createVNode"])(i,{title:"Resend Email",modelValue:a.showResendDialog,"onUpdate:modelValue":t[0]||(t[0]=function(e){return a.showResendDialog=e})},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(l,{"mail-data":a.currentEmail,onSent:c.cleanupResend,onCanceled:c.cleanupResend},null,8,["mail-data","onSent","onCanceled"])]})),_:1},8,["modelValue"])]))])}var le={name:"ComponentName",props:{person:{type:Object,required:!0},mail:{type:Array,required:!0}},data:function(){return{currentEmail:{},showResendDialog:null}},computed:{},methods:{initResend:function(e){this.currentEmail=Object(y["a"])({},e),this.showResendDialog=!0},cleanupResend:function(){this.currentEmail={},this.showResendDialog=!1,this.$store.dispatch("people/getMail",this.person)}}};const ie=B()(le,[["render",ce]]);var se=ie,ue=n("ef43"),de={name:"PersonDetail",components:{TabsContainer:E["default"],MembershipList:L,PersonProfile:S["a"],PersonMergeForm:A,CoiList:G["a"],ActivityLog:Y["a"],PersonMailLog:se,ProfilePicture:ue["a"]},props:{uuid:{required:!0,type:String}},data:function(){return{showDeleteConfirmation:!1,showMergeForm:!1,mailLoading:!1,logsLoading:!1}},watch:{uuid:{immediate:!0,handler:function(){var e=Object(V["a"])(regeneratorRuntime.mark((function e(){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.next=2,this.$store.dispatch("people/getPerson",{uuid:this.uuid});case 2:this.coordinatesPerson(this.person)&&(this.getLogEntries(),this.getMailLog());case 3:case"end":return e.stop()}}),e,this)})));function t(){return e.apply(this,arguments)}return t}()},"person.memberships":function(e){e.length>0&&(this.getLogEntries(),this.getMailLog())}},computed:Object(y["a"])(Object(y["a"])({},Object(C["b"])({person:"people/currentItem"})),{},{showModal:{get:function(){return this.$route.meta.showModal},set:function(e){e&&this.$router.push({name:"PersonEdit",params:{uuid:this.person.uuid}}),this.$router.push({name:"PersonDetail",params:{uuid:this.person.uuid}})}},sortedMailLog:function(){return Object(v["a"])(this.person.mailLog).sort((function(e,t){return e.created_at==t.created_at?0:Date.parse(e.created_at)>Date.parse(t.created_at)?-1:1}))}}),methods:{initDelete:function(){this.showDeleteConfirmation=!0},commitDelete:function(){var e=this;return Object(V["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,e.$store.dispatch("people/deletePerson",e.person);case 2:e.showDeleteConfirmation=!1,e.$router.go(-1);case 4:case"end":return t.stop()}}),t)})))()},cancelDelete:function(){this.showDeleteConfirmation=!1},initMerge:function(){this.showMergeForm=!0},handleMerged:function(){this.showMergeForm=!1,this.$router.go(-1)},handleMergeCanceled:function(){this.showMergeForm=!1},getMailLog:function(){var e=this;return Object(V["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return e.mailLoading=!0,t.next=3,e.$store.dispatch("people/getMail",e.person);case 3:e.mailLoading=!1;case 4:case"end":return t.stop()}}),t)})))()},getLogEntries:function(){var e=this;return Object(V["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return e.logsLoading=!0,t.next=3,e.fetchEntries("/api/people/".concat(e.uuid,"/activity-logs"));case 3:e.logsLoading=!1;case 4:case"end":return t.stop()}}),t)})))()}},setup:function(){return{formatDate:w["c"],logEntries:N["c"],fetchEntries:N["b"]}},onmounted:function(){this.$store.dispatch("people/clearCurrentItem")}};const be=B()(de,[["render",h]]);t["default"]=be},"768b":function(e,t,n){},"831e":function(e,t,n){"use strict";n.d(t,"c",(function(){return l})),n.d(t,"b",(function(){return i})),n.d(t,"d",(function(){return s})),n.d(t,"e",(function(){return u})),n.d(t,"a",(function(){return d}));var r=n("1da1"),o=(n("c740"),n("99af"),n("a434"),n("96cf"),n("7a23")),a=n("f96b"),c=Object(o["ref"])([]),l=Object(o["computed"])((function(){return c.value})),i=function(){var e=Object(r["a"])(regeneratorRuntime.mark((function e(t){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.next=2,a["a"].get(t).then((function(e){return e.data.data}));case 2:c.value=e.sent;case 3:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}(),s=function(){var e=Object(r["a"])(regeneratorRuntime.mark((function e(t,n){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.next=2,a["a"].post(t,n).then((function(e){return c.value.push(e.data),e.data}));case 2:return e.abrupt("return",e.sent);case 3:case"end":return e.stop()}}),e)})));return function(t,n){return e.apply(this,arguments)}}(),u=function(){var e=Object(r["a"])(regeneratorRuntime.mark((function e(t,n){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.next=2,a["a"].put(t,n).then((function(e){var t=c.value.findIndex((function(t){return t.id==e.data.id}));return c.value[t]=e.data,e.data}));case 2:return e.abrupt("return",e.sent);case 3:case"end":return e.stop()}}),e)})));return function(t,n){return e.apply(this,arguments)}}(),d=function(){var e=Object(r["a"])(regeneratorRuntime.mark((function e(t,n){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.next=2,a["a"].delete("".concat(t,"/").concat(n)).then((function(e){var t=c.value.findIndex((function(e){return e.id==n}));return c.value.splice(t,1),e}));case 2:return e.abrupt("return",e.sent);case 3:case"end":return e.stop()}}),e)})));return function(t,n){return e.apply(this,arguments)}}()},ba20:function(e,t,n){"use strict";n("494c")},ffb2:function(e,t,n){"use strict";n("4e82"),n("a4d3"),n("e01a");var r=n("7a23"),o={key:0,class:"px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200"},a={key:0,class:"flex space-x-1"},c=["onClick"],l=["onClick"],i=["innerHTML"];function s(e,t,n,s,u,d){var b=Object(r["resolveComponent"])("icon-edit"),p=Object(r["resolveComponent"])("icon-trash"),m=Object(r["resolveComponent"])("data-table"),j=Object(r["resolveComponent"])("log-entry-form"),O=Object(r["resolveComponent"])("modal-dialog"),f=Object(r["resolveComponent"])("activity-log-delete-confirmation");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[s.hasLogEntries?(Object(r["openBlock"])(),Object(r["createBlock"])(m,{key:1,fields:s.fields,data:n.logEntries,sort:s.sort,"onUpdate:sort":t[0]||(t[0]=function(e){return s.sort=e})},{"cell-id":Object(r["withCtx"])((function(t){var n=t.item;return[e.hasPermission("groups-manage")?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",a,[Object(r["createElementVNode"])("button",{class:"btn btn-xs inline-block",onClick:function(e){return s.editLogEntry(n)}},[Object(r["createVNode"])(b,{width:"12"})],8,c),null===n.activity_type?(Object(r["openBlock"])(),Object(r["createElementBlock"])("button",{key:0,onClick:function(e){return s.confirmDelete(n)},class:"btn btn-xs inline-block"},[Object(r["createVNode"])(p,{width:"12"})],8,l)):Object(r["createCommentVNode"])("",!0)])):Object(r["createCommentVNode"])("",!0)]})),"cell-description":Object(r["withCtx"])((function(e){var t=e.item;return[Object(r["createElementVNode"])("div",{innerHTML:t.description,class:"links-blue"},null,8,i)]})),_:1},8,["fields","data","sort"])):(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",o," No log entries to display ")),Object(r["createVNode"])(O,{modelValue:s.editingEntry,"onUpdate:modelValue":t[1]||(t[1]=function(e){return s.editingEntry=e}),title:"Edit log entry"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(j,{"log-entry":s.selectedEntry,"api-url":n.apiUrl,onSaved:s.closeEntryForm,onCanceled:s.closeEntryForm},null,8,["log-entry","api-url","onSaved","onCanceled"])]})),_:1},8,["modelValue"]),Object(r["createVNode"])(O,{modelValue:s.showDeleteConfirmation,"onUpdate:modelValue":t[2]||(t[2]=function(e){return s.showDeleteConfirmation=e}),title:"Delete this log entry?"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(f,{logEntry:s.selectedEntry,"api-url":n.apiUrl,onCanceled:s.closeDeleteConfirmation,onDeleted:s.closeDeleteConfirmation},null,8,["logEntry","api-url","onCanceled","onDeleted"])]})),_:1},8,["modelValue"])])}var u=n("1da1"),d=(n("96cf"),n("99af"),n("5502")),b=n("85fe"),p=n("a06a"),m=n("831e"),j={props:{logEntry:{required:!1,default:function(){return{log_date:(new Date).toISOString(),step:null,entry:""}}},apiUrl:{required:!0,type:String}},emits:["saved"],setup:function(e,t){var n=t.emit,o=e,a=Object(d["d"])(),c=Object(r["ref"])({log_date:(new Date).toISOString(),step:null,entry:""}),l=Object(r["ref"])({}),i=Object(r["computed"])((function(){return a.getters["groups/currentItemOrNew"]})),s=function(e){e&&e.created_at&&(c.value={id:e.id,log_date:new Date(Date.parse(e.created_at)).toISOString(),step:e.step,entry:e.description})},j=function(){c.value={log_date:(new Date).toISOString(),step:null,entry:""}},O=function(){var e=Object(u["a"])(regeneratorRuntime.mark((function e(){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:if(e.prev=0,!c.value.id){e.next=6;break}return e.next=4,Object(m["e"])("".concat(o.apiUrl,"/").concat(c.value.id),c.value);case 4:e.next=8;break;case 6:return e.next=8,Object(m["d"])(o.apiUrl,c.value);case 8:return e.next=10,Object(m["b"])(o.apiUrl);case 10:j(),n("saved",c.value),e.next=19;break;case 14:if(e.prev=14,e.t0=e["catch"](0),!e.t0.response||422!=e.t0.response.status||!e.t0.response.data.errors){e.next=19;break}return l.value=e.t0.response.data.errors,e.abrupt("return");case 19:case"end":return e.stop()}}),e,null,[[0,14]])})));return function(){return e.apply(this,arguments)}}(),f=function(){j(),n("canceled")};return Object(r["watch"])((function(){return o.logEntry}),(function(e){s(e)}),{immediate:!0}),function(e,t){var n=Object(r["resolveComponent"])("input-row"),o=Object(r["resolveComponent"])("button-row"),a=Object(r["resolveComponent"])("form-container");return Object(r["openBlock"])(),Object(r["createBlock"])(a,{class:"log-entry-form",ref:"form-container"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(n,{label:"Log Date",modelValue:c.value.log_date,"onUpdate:modelValue":t[0]||(t[0]=function(e){return c.value.log_date=e}),errors:l.value.log_date,type:"date",ref:"logdate"},null,8,["modelValue","errors"]),Object(r["unref"])(i).isVcep()?(Object(r["openBlock"])(),Object(r["createBlock"])(p["default"],{key:0,errors:l.value.step,modelValue:c.value.step,"onUpdate:modelValue":t[1]||(t[1]=function(e){return c.value.step=e})},null,8,["errors","modelValue"])):Object(r["createCommentVNode"])("",!0),Object(r["createVNode"])(n,{label:"Entry",errors:l.value.entry},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(b["default"],{modelValue:c.value.entry,"onUpdate:modelValue":t[2]||(t[2]=function(e){return c.value.entry=e})},null,8,["modelValue"])]})),_:1},8,["errors"]),Object(r["createVNode"])(o,null,{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("button",{class:"btn",onClick:f},"Cancel"),Object(r["createElementVNode"])("button",{class:"btn blue",onClick:O},"Save")]})),_:1})]})),_:1},512)}}};const O=j;var f=O,g=(n("b0c0"),Object(r["createElementVNode"])("p",null,"You are about to delete the following log entry:",-1)),h={class:"border-y py-2"},v=["innerHTML"],y={key:0,class:"ml-4 mt-2 mb-4 text-gray-700 text-sm"},V=Object(r["createElementVNode"])("div",null,"This can not be undone. Are you sure you want to continue?",-1),C={key:0,class:"bg-red-200 text-red-900 rounded p-2 my-2"};function w(e,t,n,o,a,c){var l=Object(r["resolveComponent"])("button-row");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[g,Object(r["createElementVNode"])("div",h,[Object(r["createElementVNode"])("blockquote",null,[Object(r["createElementVNode"])("div",{innerHTML:n.logEntry.description},null,8,v)]),n.logEntry.causer?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",y,"Logged by "+Object(r["toDisplayString"])(n.logEntry.causer.name)+", "+Object(r["toDisplayString"])(o.logDate),1)):Object(r["createCommentVNode"])("",!0)]),V,o.flattenedErrors.length>0?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",C,[Object(r["createElementVNode"])("ul",null,[(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(o.flattenedErrors,(function(e,t){return Object(r["openBlock"])(),Object(r["createElementBlock"])("li",{key:t},Object(r["toDisplayString"])(e),1)})),128))])])):Object(r["createCommentVNode"])("",!0),Object(r["createVNode"])(l,{onCanceled:t[0]||(t[0]=function(t){return e.$emit("canceled")}),onSubmitted:o.deleteLogEntry,submitText:"Delete Log Entry"},null,8,["onSubmitted"])])}n("0481"),n("4069"),n("07ac");var N=n("e328"),E=n("033d"),x={name:"ConfirmDeleteLogEntry",props:{logEntry:{required:!0,type:Object},apiUrl:{required:!0,type:String}},emits:["deleted","canceled"],setup:function(e,t){var n=Object(r["ref"])({}),o=Object(r["computed"])((function(){return Object(N["b"])(e.logEntry.log_date)})),a=Object(r["computed"])((function(){return Object.values(n.value).flat()})),c=function(){var r=Object(u["a"])(regeneratorRuntime.mark((function r(){return regeneratorRuntime.wrap((function(r){while(1)switch(r.prev=r.next){case 0:return r.prev=0,r.next=3,Object(m["a"])(e.apiUrl,e.logEntry.id);case 3:return t.emit("deleted"),r.next=6,Object(m["b"])(e.apiUrl);case 6:r.next=14;break;case 8:if(r.prev=8,r.t0=r["catch"](0),!Object(E["a"])(r.t0)){r.next=13;break}return n.value=r.t0.response.data.errors,r.abrupt("return");case 13:n.value={a:r.t0.message};case 14:case"end":return r.stop()}}),r,null,[[0,8]])})));return function(){return r.apply(this,arguments)}}();return{errors:n,logDate:o,flattenedErrors:a,deleteLogEntry:c}}},k=(n("ba20"),n("6b0d")),D=n.n(k);const _=D()(x,[["render",w]]);var B=_,M=[{name:"created_at",label:"Date & Time",sortName:"created_at",sortable:!0,resolveValue:function(e){var t=e.created_at;return t?Object(N["b"])(t):null},type:Date,colspan:2,class:"w-16"},{name:"created_at",label:"Time",sortName:"created_at",hideHeader:!0,sortable:!1,resolveValue:function(e){var t=e.created_at;return t?Object(N["d"])(t):null},class:"w-28",type:Date},{name:"description",label:"Entry",sortable:!0,type:String},{name:"causer.name",label:"User",sortable:!0},{name:"id",label:"",sortable:!1}],L={components:{LogEntryForm:f,ActivityLogDeleteConfirmation:B},props:{logEntries:{required:!0,type:Array},apiUrl:{required:!0,type:String}},setup:function(e){var t=Object(r["ref"])({field:"created_at",desc:!0}),n=Object(r["ref"])({}),o=Object(r["ref"])(!1),a=Object(r["ref"])(!1),c=Object(r["computed"])((function(){return e.logEntries.length>0})),l=function(e){o.value=!0,n.value=e},i=function(){o.value=!1,n.value={}},s=function(e){n.value=e,a.value=!0},u=function(){a.value=!1,n.value={}};return{fields:Object(r["ref"])(M),sort:t,selectedEntry:n,editingEntry:o,hasLogEntries:c,editLogEntry:l,closeEntryForm:i,showDeleteConfirmation:a,confirmDelete:s,closeDeleteConfirmation:u}}};n("03c4");const S=D()(L,[["render",s]]);t["a"]=S}}]);
//# sourceMappingURL=chunk-39f36daa.71dfc6e4.js.map