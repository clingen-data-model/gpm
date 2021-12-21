(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d0d63f1"],{7277:function(e,t,n){"use strict";n.r(t);n("b0c0");var o=n("7a23"),r=Object(o["createTextVNode"])(" Dashboard "),c={class:"note font-normal"},a=Object(o["createElementVNode"])("br",null,null,-1),i=Object(o["createElementVNode"])("br",null,null,-1),u=Object(o["createTextVNode"])(" Complete this COI Disclosure "),l={key:0,class:"well"};function s(e,t,n,s,p,d){var b=Object(o["resolveComponent"])("notification-item"),f=Object(o["resolveComponent"])("router-link"),m=Object(o["resolveComponent"])("static-alert"),O=Object(o["resolveComponent"])("badge"),j=Object(o["resolveComponent"])("data-table"),v=Object(o["resolveComponent"])("tab-item"),g=Object(o["resolveComponent"])("person-profile"),C=Object(o["resolveComponent"])("coi-list"),w=Object(o["resolveComponent"])("tabs-container");return Object(o["openBlock"])(),Object(o["createElementBlock"])("div",null,[Object(o["createElementVNode"])("h1",null,[r,Object(o["createElementVNode"])("div",c," User ID: "+Object(o["toDisplayString"])(s.user.id)+" | Person ID: "+Object(o["toDisplayString"])(s.user.person?s.user.person.id:"no person!!"),1)]),Object(o["createVNode"])(o["TransitionGroup"],{tag:"div",name:"slide-fade-down"},{default:Object(o["withCtx"])((function(){return[(Object(o["openBlock"])(!0),Object(o["createElementBlock"])(o["Fragment"],null,Object(o["renderList"])(s.notifications,(function(e){return Object(o["openBlock"])(),Object(o["createBlock"])(b,{key:e.id,notification:e,class:"mt-2",onRemoved:function(t){return s.removeNotification(e)},variant:e.data.type},null,8,["notification","onRemoved","variant"])})),128)),(Object(o["openBlock"])(!0),Object(o["createElementBlock"])(o["Fragment"],null,Object(o["renderList"])(s.user.person.membershipsWithPendingCois,(function(e){return Object(o["openBlock"])(),Object(o["createBlock"])(m,{key:e.id,class:"mt-2 font-bold",variant:"warning"},{default:Object(o["withCtx"])((function(){return[Object(o["createTextVNode"])(" You have a pending Conflict of Interest Disclosure for "+Object(o["toDisplayString"])(e.group.name)+". ",1),a,i,Object(o["createVNode"])(f,{to:{name:"alt-coi",params:{name:e.group.name,code:e.group.expert_panel.coi_code}},class:"btn"},{default:Object(o["withCtx"])((function(){return[u]})),_:2},1032,["to"])]})),_:2},1024)})),128))]})),_:1}),Object(o["createVNode"])(w,{class:"mt-8"},{default:Object(o["withCtx"])((function(){return[Object(o["createVNode"])(v,{label:"Your Groups"},{default:Object(o["withCtx"])((function(){return[s.groups.length?(Object(o["openBlock"])(),Object(o["createBlock"])(j,{key:1,data:s.groups,fields:s.groupFields,sort:s.groupSort,"onUpdate:sort":t[0]||(t[0]=function(e){return s.groupSort=e}),onRowClick:s.navigateToGroup,"row-class":"cursor-pointer"},{"cell-status_name":Object(o["withCtx"])((function(e){var t=e.value;return[Object(o["createVNode"])(O,{color:s.groupBadgeColor(t)},{default:Object(o["withCtx"])((function(){return[Object(o["createTextVNode"])(Object(o["toDisplayString"])(t),1)]})),_:2},1032,["color"])]})),_:1},8,["data","fields","sort","onRowClick"])):(Object(o["openBlock"])(),Object(o["createElementBlock"])("div",l,"You are not assigned to any groups."))]})),_:1}),Object(o["createVNode"])(v,{label:"Your Info"},{default:Object(o["withCtx"])((function(){return[Object(o["createVNode"])(g,{person:s.personFromStore},null,8,["person"])]})),_:1}),Object(o["createVNode"])(v,{label:"COIs"},{default:Object(o["withCtx"])((function(){return[Object(o["createVNode"])(C,{person:s.user.person},null,8,["person"])]})),_:1})]})),_:1})])}var p=n("1da1"),d=(n("c740"),n("a434"),n("d81d"),n("4de4"),n("96cf"),n("5502")),b=n("6c02"),f=n("db04"),m=n("c53a"),O=n("6a6b"),j=n("c226"),v=n("da07"),g=n("ae23"),C={name:"Dashboard",components:{CoiList:O["a"],NotificationItem:m["default"],PersonProfile:j["a"]},data:function(){return{}},props:{},setup:function(){var e=Object(d["d"])(),t=Object(b["d"])(),n=Object(o["computed"])((function(){return e.getters["currentUser"]})),r=Object(o["computed"])((function(){return e.getters["people/currentItem"]||new v["a"]})),c=function(){n.value.id&&n.value.person&&n.value.person.id&&(e.commit("people/addItem",n.value.person),e.commit("people/setCurrentItemIndex",n.value.person))};Object(o["watch"])((function(){return n}),(function(){c()})),Object(o["onMounted"])((function(){c()}));var a=Object(o["ref"])(!1),i=Object(o["ref"])([]),u=function(){var e=Object(p["a"])(regeneratorRuntime.mark((function e(){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return a.value=!0,e.next=3,f["a"].get("/api/people/".concat(n.value.person.uuid,"/notifications/unread")).then((function(e){return e.data}));case 3:i.value=e.sent,a.value=!1;case 5:case"end":return e.stop()}}),e)})));return function(){return e.apply(this,arguments)}}(),l=function(e){var t=i.value.findIndex((function(t){return t.id==e.id}));t>-1&&i.value.splice(t,1)},s=Object(o["computed"])((function(){return n.value.memberships.map((function(e){return e.group})).filter((function(e){return null!==e})).map((function(e){return new g["a"](e)}))})),m=Object(o["ref"])([{name:"displayName",sortable:!0,type:String},{name:"status.name",label:"Status",sortable:!0,type:String},{name:"info",sortable:!1,type:String}]),O=Object(o["ref"])({field:"displayName",desc:!1}),j=function(e){var t={Active:"green","Pending-Approval":"blue",Retired:"yellow",Removed:"red"};return t[e]||"blue"};return Object(o["onMounted"])((function(){u()})),{user:n,personFromStore:r,loadingNotifications:a,notifications:i,groups:s,groupSort:O,groupFields:m,groupBadgeColor:j,getNotifications:u,removeNotification:l,navigateToGroup:function(e){t.push({name:"GroupDetail",params:{uuid:e.uuid}})}}}};C.render=s;t["default"]=C}}]);
//# sourceMappingURL=chunk-2d0d63f1.b095b2ff.js.map