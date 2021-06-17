(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["application-index","create-application-form"],{"0049":function(e,t,n){"use strict";n.r(t);var a=n("7a23"),r=Object(a["withScopeId"])("data-v-4ce544c6");Object(a["pushScopeId"])("data-v-4ce544c6");var o={class:"home"},c={class:"mb-2 mt-4"},i={class:"tabs"},l=Object(a["createTextVNode"])("VCEPS"),s=Object(a["createTextVNode"])("GCEPS"),d={class:"p-4 border rounded-tr-lg rounded-b-lg bg-white"};Object(a["popScopeId"])();var p=r((function(e,t,n,p,u,b){var f=Object(a["resolveComponent"])("router-link"),m=Object(a["resolveComponent"])("router-view"),j=Object(a["resolveComponent"])("create-application-form"),h=Object(a["resolveComponent"])("modal-dialog");return Object(a["openBlock"])(),Object(a["createBlock"])("div",o,[Object(a["createVNode"])("button",{onClick:t[1]||(t[1]=function(e){return u.showModal=!0}),class:"btn blue"},"Initiate Application"),Object(a["createVNode"])("div",c,[Object(a["createVNode"])("div",i,[Object(a["createVNode"])(f,{to:{name:"vceps"},class:"tab"},{default:r((function(){return[l]})),_:1}),Object(a["createVNode"])(f,{to:{name:"gceps"},class:"tab"},{default:r((function(){return[s]})),_:1})]),Object(a["createVNode"])("div",d,[Object(a["createVNode"])(m)])]),Object(a["createVNode"])(h,{modelValue:u.showModal,"onUpdate:modelValue":t[3]||(t[3]=function(e){return u.showModal=e}),size:"md",onClosed:t[4]||(t[4]=function(t){return e.$refs.initiateform.initForm()})},{default:r((function(){return[Object(a["createVNode"])(j,{name:"modal",onCanceled:t[2]||(t[2]=function(e){return u.showModal=!1}),onSaved:b.handleApplicationInitiated,ref:"initiateform"},null,8,["onSaved"])]})),_:1},8,["modelValue"])])})),u=n("3f17"),b={name:"ApplicationsIndex",components:{CreateApplicationForm:u["default"]},data:function(){return{showModal:!1}},computed:{},methods:{handleApplicationInitiated:function(e){this.showModal=!1,this.$router.push({name:"AddContact",params:{uuid:e.uuid}})}}};n("6531");b.render=p,b.__scopeId="data-v-4ce544c6";t["default"]=b},"008e":function(e,t,n){},"2b61":function(e,t,n){"use strict";n.r(t);var a=n("7a23");function r(e,t,n,r,o,c){var i=Object(a["resolveComponent"])("applications-table");return Object(a["openBlock"])(),Object(a["createBlock"])("div",null,[Object(a["createVNode"])(i,{"ep-type-id":1})])}var o=n("eb82"),c={components:{ApplicationsTable:o["a"]},props:{},data:function(){return{}}};c.render=r;t["default"]=c},"3f17":function(e,t,n){"use strict";n.r(t);n("b0c0");var a=n("7a23"),r=Object(a["createVNode"])("h4",{class:"text-xl font-semibold pb-2 border-b mb-4"},"Initiate Application",-1),o=Object(a["createVNode"])("option",{value:null},"Select...",-1),c=Object(a["createTextVNode"])("   "),i=Object(a["createTextVNode"])("   "),l=Object(a["createVNode"])("label",{for:"show-initiation-checkbox"},"Backdate this initiation",-1);function s(e,t,n,s,d,p){var u=this,b=Object(a["resolveComponent"])("input-row"),f=Object(a["resolveComponent"])("button-row"),m=Object(a["resolveComponent"])("form-container");return Object(a["openBlock"])(),Object(a["createBlock"])(m,{onKeydown:Object(a["withKeys"])(p.save,["enter"])},{default:Object(a["withCtx"])((function(){return[r,Object(a["createVNode"])(b,{label:"Working Name",errors:d.errors.working_name,type:"text",modelValue:u.app.working_name,"onUpdate:modelValue":t[1]||(t[1]=function(e){return u.app.working_name=e}),placeholder:"A recognizable name"},null,8,["errors","modelValue"]),Object(a["createVNode"])(b,{label:"CDWG",errors:d.errors.cdwg_id},{default:Object(a["withCtx"])((function(){return[Object(a["withDirectives"])(Object(a["createVNode"])("select",{"onUpdate:modelValue":t[2]||(t[2]=function(e){return d.app.cdwg_id=e})},[o,(Object(a["openBlock"])(!0),Object(a["createBlock"])(a["Fragment"],null,Object(a["renderList"])(e.cdwgs,(function(e){return Object(a["openBlock"])(),Object(a["createBlock"])("option",{key:e.id,value:e.id},Object(a["toDisplayString"])(e.name),9,["value"])})),128))],512),[[a["vModelSelect"],d.app.cdwg_id]])]})),_:1},8,["errors"]),Object(a["createVNode"])(b,{label:"EP Type",errors:d.errors.ep_type_id},{default:Object(a["withCtx"])((function(){return[Object(a["createVNode"])("div",null,[(Object(a["openBlock"])(!0),Object(a["createBlock"])(a["Fragment"],null,Object(a["renderList"])(d.epTypes,(function(e){return Object(a["openBlock"])(),Object(a["createBlock"])("div",{key:e.id},[Object(a["withDirectives"])(Object(a["createVNode"])("input",{type:"radio",value:e.id,"onUpdate:modelValue":t[3]||(t[3]=function(e){return d.app.ep_type_id=e}),id:"ep-".concat(e.id,"-radio")},null,8,["value","id"]),[[a["vModelRadio"],d.app.ep_type_id]]),c,Object(a["createVNode"])("label",{for:"ep-".concat(e.id,"-radio")},Object(a["toDisplayString"])(e.name),9,["for"])])})),128))])]})),_:1},8,["errors"]),Object(a["createVNode"])(b,{errors:d.errors.date_initiated},{default:Object(a["withCtx"])((function(){return[Object(a["createVNode"])("div",null,[Object(a["createVNode"])("div",null,[Object(a["withDirectives"])(Object(a["createVNode"])("input",{type:"checkbox","onUpdate:modelValue":t[4]||(t[4]=function(e){return d.showInitiationDate=e}),id:"show-initiation-checkbox"},null,512),[[a["vModelCheckbox"],d.showInitiationDate]]),i,l]),Object(a["withDirectives"])(Object(a["createVNode"])(b,{type:"date",label:"Initiation Date",modelValue:d.app.date_initiated,"onUpdate:modelValue":t[5]||(t[5]=function(e){return d.app.date_initiated=e})},null,8,["modelValue"]),[[a["vShow"],d.showInitiationDate]])])]})),_:1},8,["errors"]),Object(a["createVNode"])(f,null,{default:Object(a["withCtx"])((function(){return[Object(a["createVNode"])("button",{class:"btn",onClick:t[6]||(t[6]=function(){return p.cancel&&p.cancel.apply(p,arguments)})},"Cancel"),Object(a["createVNode"])("button",{class:"btn blue",onClick:t[7]||(t[7]=function(){return p.save&&p.save.apply(p,arguments)})},"Initiate Application")]})),_:1})]})),_:1},8,["onKeydown"])}n("b64b"),n("96cf");var d=n("1da1"),p=n("5530"),u=n("5502"),b=n("e328"),f={name:"CreateApplicationForm",props:{},emits:["canceled","saved"],data:function(){return{visible:!1,showInitiationDate:!1,app:{working_name:null,cdwg_id:null,ep_type_id:null,date_initiated:Object(b["a"])(new Date)},epTypes:[{name:"GCEP",id:1},{name:"VCEP",id:2}],errors:{}}},computed:Object(p["a"])(Object(p["a"])({},Object(u["b"])({cdwgs:"cdwgs/all"})),{},{hasErrors:function(){return Object.keys(this.errors).length>0}}),watch:{"app.working_name":function(){this.clearErrors("working_name")},"app.cdwg_id":function(){this.clearErrors("cdwg_id")},"app.ep_type_id":function(){this.clearErrors("ep_type_id")}},methods:{cancel:function(){this.initForm(),this.$emit("canceled")},save:function(){var e=this;return Object(d["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,e.$store.dispatch("applications/initiateApplication",e.app);case 3:e.$emit("saved",e.app),t.next=12;break;case 6:if(t.prev=6,t.t0=t["catch"](0),!t.t0.response||422!=t.t0.response.status||!t.t0.response.data.errors){t.next=11;break}return e.errors=t.t0.response.data.errors,t.abrupt("return");case 11:throw t.t0;case 12:case"end":return t.stop()}}),t,null,[[0,6]])})))()},initForm:function(){this.initErrors(),this.initAppData()},initAppData:function(){this.app={working_name:null,cdwg_id:null,ep_type_id:null,date_initiated:Object(b["a"])(new Date)}},clearErrors:function(e){e?delete this.errors[e]:this.initErrors()},initErrors:function(){this.errors={}}}};f.render=s;t["default"]=f},5257:function(e,t,n){"use strict";n("4de4"),n("caad"),n("b64b"),n("ac1f"),n("2532"),n("5319");var a=n("5530"),r=n("6c02"),o=n("7a23");t["a"]=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null,t=Object(r["d"])(),n=Object(r["c"])();e=e||{field:"name",desc:!1};var c=Object(o["computed"])({immediate:!0,get:function(){return Object.keys(n.query).includes("sort-field")?{field:n.query["sort-field"],desc:Boolean(parseInt(n.query["sort-desc"]))}:e},set:function(e){var r={"sort-field":e.field,"sort-desc":e.desc?1:0},o=Object(a["a"])(Object(a["a"])({},n.query),r);t.replace({path:n.path,query:o})}}),i=Object(o["computed"])({set:function(e){var r=n.query,o=n.path,c=Object(a["a"])({},r);e?c=Object(a["a"])(Object(a["a"])({},r),{filter:e}):delete c.filter,t.replace({path:o,query:c})},get:function(){return n.query.filter},immediate:!0});return{sort:c,filter:i}}},6531:function(e,t,n){"use strict";n("008e")},d659:function(e,t,n){"use strict";n.r(t);var a=n("7a23");function r(e,t,n,r,o,c){var i=Object(a["resolveComponent"])("applications-table");return Object(a["openBlock"])(),Object(a["createBlock"])("div",null,[Object(a["createVNode"])(i,{"ep-type-id":2})])}var o=n("eb82"),c={components:{ApplicationsTable:o["a"]}};c.render=r;t["default"]=c},eb82:function(e,t,n){"use strict";n("4de4"),n("b0c0");var a=n("7a23"),r={class:"flex justify-between"},o={class:"mb-1 flex space-x-2"},c=Object(a["createTextVNode"])("Filter: "),i=Object(a["createTextVNode"])(" Show completed ");function l(e,t,n,l,s,d){var p=Object(a["resolveComponent"])("data-table");return Object(a["openBlock"])(),Object(a["createBlock"])("div",null,[Object(a["createVNode"])("div",r,[Object(a["createVNode"])("div",o,[Object(a["createVNode"])("label",null,[c,Object(a["withDirectives"])(Object(a["createVNode"])("input",{type:"text",class:"sm","onUpdate:modelValue":t[1]||(t[1]=function(e){return l.filter=e}),placeholder:"filter"},null,512),[[a["vModelText"],l.filter]])]),Object(a["createVNode"])("label",null,[Object(a["withDirectives"])(Object(a["createVNode"])("input",{type:"checkbox","onUpdate:modelValue":t[2]||(t[2]=function(e){return d.showCompleted=e})},null,512),[[a["vModelCheckbox"],d.showCompleted]]),i])]),Object(a["createVNode"])("div",null,[Object(a["createVNode"])("button",{class:["btn btn-xs",{blue:0==d.showAllInfo}],onClick:t[3]||(t[3]=function(e){return d.showAllInfo=0})},"Summary",2),Object(a["createVNode"])("button",{class:["btn btn-xs",{blue:1==d.showAllInfo}],onClick:t[4]||(t[4]=function(e){return d.showAllInfo=1})},"All Info",2)])]),Object(a["createVNode"])(p,{data:d.filteredData,fields:d.selectedFields,"filter-term":l.filter,"row-click-handler":d.goToApplication,"row-class":"cursor-pointer",sort:l.sort,"onUpdate:sort":t[5]||(t[5]=function(e){return l.sort=e}),style:d.remainingHeight,class:"overflow-auto",ref:"table"},{"cell-contacts":Object(a["withCtx"])((function(e){var t=e.item;return[Object(a["createVNode"])("ul",null,[(Object(a["openBlock"])(!0),Object(a["createBlock"])(a["Fragment"],null,Object(a["renderList"])(t.contacts,(function(e){return Object(a["openBlock"])(),Object(a["createBlock"])("li",{key:e.id},[Object(a["createVNode"])("small",null,[Object(a["createVNode"])("a",{href:"mailto:".concat(e.email),class:"text-blue-500"},Object(a["toDisplayString"])(e.name),9,["href"])])])})),128))])]})),"cell-latest_log_entry_description":Object(a["withCtx"])((function(e){var t=e.value;return[Object(a["createVNode"])("div",{innerHTML:t},null,8,["innerHTML"])]})),"cell-latest_pending_next_action_entry":Object(a["withCtx"])((function(e){var t=e.value;return[Object(a["createVNode"])("div",{innerHTML:t},null,8,["innerHTML"])]})),_:1},8,["data","fields","filter-term","row-click-handler","sort","style"])])}n("99af"),n("caad"),n("a9e3"),n("b64b"),n("ac1f"),n("2532"),n("5319");var s=n("2909"),d=n("5530"),p=n("5502"),u=n("e328"),b=n("5257"),f={components:{},props:{epTypeId:{type:Number,default:null}},data:function(){return{fields:[{name:"id",label:"ID",type:Number,sortable:!0},{name:"name",label:"Name",type:String,sortable:!0},{name:"cdwg.name",label:"CDWG",type:String,sortable:!0},{name:"current_step",label:"Current Step",type:Number,sortable:!0,resolveValue:function(e){return e.isCompleted?"Completed":e.current_step},resolveSort:function(e){return e.isCompleted?5:e.current_step}},{name:"latest_log_entry.created_at",label:"Last Activity",type:String,sortable:!0,resolveValue:function(e){return e&&e.latest_log_entry?Object(u["a"])(e.latest_log_entry.created_at):null},colspan:2,headerClass:["max-w-sm"],class:["min-w-28"]},{name:"latest_log_entry.description",label:"Last Activity",type:String,hideHeader:!0,class:["max-w-48","truncate"]},{name:"latest_pending_next_action.entry",label:"Next Action",type:String,sortable:!1,class:["min-w-28","max-w-xs","truncate"]}],allInfoFields:[{name:"contacts",label:"Contacts",type:Array,sortable:!1,class:["min-w-40"],step:1},{name:"first_scope_document.date_received",label:2==this.epTypeId?"Step 1 Received":"Application Received",type:Date,sortable:!0,class:["min-w-28"],step:1},{name:"approval_dates.step 1",label:2==this.epTypeId?"Step 1 Approved":"Application Approved",type:Date,sortable:!0,class:["min-w-28"],step:1},{name:"approval_dates.step 2",label:"Step 2 Approved",type:Date,sortable:!0,class:["min-w-28"],step:2},{name:"approval_dates.step 3",label:"Step 3 Approved",type:Date,sortable:!0,class:["min-w-28"],step:3},{name:"first_final_document.date_received",label:"Step 4 Received",type:Date,sortable:!0,class:["min-w-28"],step:4},{name:"approval_dates.step 4",label:"Step 4 Approved",type:Date,sortable:!0,class:["min-w-28"],step:4}]}},computed:Object(d["a"])(Object(d["a"])({},Object(p["b"])({applications:"applications/all"})),{},{filteredData:function(){var e=this;return this.applications.filter((function(t){return!e.epTypeId||t.ep_type_id==e.epTypeId})).filter((function(t){return!!e.showCompleted||null==t.date_completed}))},showCompleted:{set:function(e){var t=this.$route.query,n=this.$route.path,a=Object(d["a"])({},t);e?a=Object(d["a"])(Object(d["a"])({},t),{"show-completed":1}):delete a["show-completed"],this.$router.replace({path:n,query:a})},get:function(){return Boolean(parseInt(this.$route.query["show-completed"]))},immediate:!0},selectedFields:function(){if(1==this.showAllInfo){var e=2==this.epTypeId?[1,2,3,4]:[1],t=this.allInfoFields.filter((function(t){return e.includes(t.step)}));return[].concat(Object(s["a"])(this.fields),Object(s["a"])(t))}return this.fields},showAllInfo:{immediate:!0,get:function(){return Object.keys(this.$route.query).includes("showAllInfo")?this.$route.query.showAllInfo:0},set:function(e){var t=Object(d["a"])({},this.$route.query);t.showAllInfo=e,this.$router.replace({path:this.$route.path,query:t})}},remainingHeight:function(){return{height:"calc(100vh - 220px)"}}}),methods:{getApplications:function(){var e={with:["latestLogEntry","latestPendingNextAction","type","contacts","firstScopeDocument","firstFinalDocument"]},t={};Object.keys(t).length>0&&(e.where=t),this.$store.dispatch("applications/getApplications",e)},goToApplication:function(e){this.$router.push({name:"ApplicationDetail",params:{uuid:e.uuid}})}},mounted:function(){this.getApplications()},setup:function(){var e=Object(b["a"])(),t=e.sort,n=e.filter;return{sort:t,filter:n}}};f.render=l;t["a"]=f}}]);
//# sourceMappingURL=application-index.e4417882.js.map