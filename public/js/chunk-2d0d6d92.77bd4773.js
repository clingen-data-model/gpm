(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d0d6d92"],{"73ff":function(e,t,n){"use strict";n.r(t);n("b0c0");var r=n("7a23"),o={class:"pb-4"},a=Object(r["createTextVNode"])("People"),c={class:"flex justify-between items-center"},i=Object(r["createElementVNode"])("strong",null,"Email:",-1),l=Object(r["createElementVNode"])("strong",null,"Institution:",-1),s=Object(r["createTextVNode"])(" docs "),u={key:0,class:"well"},d=["innerHTML"];function b(e,t,n,b,p,m){var j=Object(r["resolveComponent"])("note"),O=Object(r["resolveComponent"])("icon-edit"),f=Object(r["resolveComponent"])("router-link"),h=Object(r["resolveComponent"])("dictionary-row"),w=Object(r["resolveComponent"])("membership-list"),g=Object(r["resolveComponent"])("tab-item"),V=Object(r["resolveComponent"])("person-profile"),N=Object(r["resolveComponent"])("coi-list"),C=Object(r["resolveComponent"])("tabs-container"),v=Object(r["resolveComponent"])("router-view"),x=Object(r["resolveComponent"])("modal-dialog");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createElementVNode"])("header",o,[Object(r["createVNode"])(j,null,{default:Object(r["withCtx"])((function(){return[a]})),_:1}),Object(r["createElementVNode"])("h1",c,[Object(r["createElementVNode"])("div",null,[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.person.name)+" ",1),Object(r["createVNode"])(j,null,{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])("ID: "+Object(r["toDisplayString"])(e.person.id),1)]})),_:1})]),e.hasPermission("people-manage")||e.userIsPerson(e.person)?(Object(r["openBlock"])(),Object(r["createBlock"])(f,{key:0,to:"/people/".concat(n.uuid,"/edit"),class:"btn btn-xs flex-grow-0"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(O,{width:"16",heigh:"16"})]})),_:1},8,["to"])):Object(r["createCommentVNode"])("",!0)]),Object(r["createVNode"])(h,{label:"Email"},{label:Object(r["withCtx"])((function(){return[i]})),default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(e.person.email),1)]})),_:1}),Object(r["createVNode"])(h,{label:"Institution"},{label:Object(r["withCtx"])((function(){return[l]})),default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(" "+Object(r["toDisplayString"])(e.person.institution?e.person.institution.name:null),1)]})),_:1})]),Object(r["createVNode"])(C,null,{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(g,{label:"groups"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(w,{person:e.person},null,8,["person"])]})),_:1}),Object(r["createVNode"])(g,{label:"Info"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(V,{person:e.person},null,8,["person"])]})),_:1}),Object(r["createVNode"])(g,{label:"Conflict of Interest"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(N,{person:e.person},null,8,["person"])]})),_:1}),Object(r["createVNode"])(g,{label:"Documents"},{default:Object(r["withCtx"])((function(){return[s]})),_:1}),Object(r["createVNode"])(g,{label:"Email Log"},{default:Object(r["withCtx"])((function(){return[0==m.sortedMailLog.length?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",u,Object(r["toDisplayString"])(e.titleCase(e.person.first_name))+" has not received any mail via the GPM. ",1)):Object(r["createCommentVNode"])("",!0),(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(m.sortedMailLog,(function(e){return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",{class:"w-3/4 my-4 p-4 border",key:e.id},[Object(r["createVNode"])(h,{label:"Date/Time"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(b.formatDate(e.created_at)),1)]})),_:2},1024),Object(r["createVNode"])(h,{label:"Subject"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(e.subject),1)]})),_:2},1024),Object(r["createVNode"])(h,{label:"Body"},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("div",{innerHTML:e.body},null,8,d)]})),_:2},1024)])})),128))]})),_:1})]})),_:1}),Object(r["createVNode"])(x,{modelValue:m.showModal,"onUpdate:modelValue":t[0]||(t[0]=function(e){return m.showModal=e}),title:e.$route.meta.title},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(v,{name:"modal"})]})),_:1},8,["modelValue","title"])])}var p=n("1da1"),m=n("2909"),j=n("5530"),O=(n("96cf"),n("4e82"),n("5502")),f=n("e328"),h=n("b442");function w(e,t,n,o,a,c){var i=Object(r["resolveComponent"])("data-table");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createVNode"])(i,{fields:a.fields,data:c.memberships,sort:a.sort,"onUpdate:sort":t[0]||(t[0]=function(e){return a.sort=e}),rowClickHandler:c.goToGroup,"row-class":function(){return"cursor-pointer"}},null,8,["fields","data","sort","rowClickHandler","row-class"])])}n("a15b"),n("d81d");var g=n("5313"),V={name:"MembershipList",props:{person:{type:Object,required:!0}},data:function(){return{sort:{field:"group.displayName",desc:!1},fields:[{name:"group.displayName",type:String,sortable:!0,label:"Name"},{name:"group.type.name",type:String,sortable:!0,label:"Type"},{name:"roles",type:String,sortable:!1,label:"Roles",resolveValue:function(e){return e.roles.map((function(e){return e.name})).join(", ")}},{name:"permissions",type:String,sortable:!1,label:"Permissions",resolveValue:function(e){return e.permissions.map((function(e){return e.name})).join(", ")}},{name:"start_date",type:Date,sortable:!0,label:"Started"},{name:"end_date",type:Date,sortable:!0,label:"Ended"}]}},computed:{memberships:function(){return this.person.memberships?this.person.memberships.map((function(e){return e.group&&(e.group=new g["b"](e.group)),e})):[]}},methods:{goToGroup:function(e){this.$router.push({name:"GroupDetail",params:{uuid:e.group.uuid}})}}};V.render=w;var N=V,C=n("c226"),v=n("6a6b"),x={name:"PersonDetail",components:{TabsContainer:h["default"],MembershipList:N,PersonProfile:C["a"],CoiList:v["a"]},props:{uuid:{required:!0,type:String}},data:function(){return{emails:[]}},watch:{uuid:{immediate:!0,handler:function(){this.$store.dispatch("people/getPerson",{uuid:this.uuid})}}},computed:Object(j["a"])(Object(j["a"])({},Object(O["b"])({person:"people/currentItem"})),{},{showModal:{get:function(){return this.$route.meta.showModal},set:function(e){e&&this.$router.push({name:"PersonEdit",params:{uuid:this.person.uuid}}),this.$router.push({name:"PersonDetail",params:{uuid:this.person.uuid}})}},sortedMailLog:function(){return Object(m["a"])(this.person.mailLog).sort((function(e,t){return e.created_at==t.created_at?0:Date.parse(e.created_at)>Date.parse(t.created_at)?-1:1}))}}),methods:{},setup:function(){return{formatDate:f["b"]}},mounted:function(){var e=this;return Object(p["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,e.$store.dispatch("people/getPerson",{uuid:e.uuid});case 2:e.$store.dispatch("people/getMail",e.person);case 3:case"end":return t.stop()}}),t)})))()}};x.render=b;t["default"]=x}}]);
//# sourceMappingURL=chunk-2d0d6d92.77bd4773.js.map