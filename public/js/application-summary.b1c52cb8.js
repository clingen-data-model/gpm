"use strict";(self["webpackChunkepam"]=self["webpackChunkepam"]||[]).push([[300],{2790:function(e,t,l){l.d(t,{Z:function(){return n}});l(6699);var r=l(2119),a=l(6252);function n(e=null){const t=(0,r.tv)(),l=(0,r.yj)();e||(console.log('Warning: defaultSort is deprecated.  Please provide a sort object: {field: "fieldname", desc: boolean}'),e=e||{field:"name",desc:!1});const n=(0,a.Fl)({immediate:!0,get(){return Object.keys(l.query).includes("sort-field")?{field:l.query["sort-field"],desc:Boolean(parseInt(l.query["sort-desc"]))}:e},set(e){const r={"sort-field":e.field,"sort-desc":e.desc?1:0},a={...l.query,...r};t.replace({path:l.path,query:a})}}),s=(0,a.Fl)({set(e){let r=l.query,a=l.path,n={...r};e?n={...r,filter:e}:delete n.filter,t.replace({path:a,query:n})},get(){return l.query.filter},immediate:!0});return{sort:n,filter:s}}},9197:function(e,t,l){l.d(t,{Z:function(){return b}});var r=l(6252),a=l(9963),n=l(3577);const s={class:"flex justify-between"},p={class:"mb-1 flex space-x-2"},o=(0,r.Uk)("Filter: "),i=["href"],u=["innerHTML"],c=["innerHTML"];function d(e,t,l,d,m,h){const _=(0,r.up)("checkbox"),f=(0,r.up)("data-table");return(0,r.wg)(),(0,r.iD)("div",null,[(0,r._)("div",s,[(0,r._)("div",p,[(0,r._)("label",null,[o,(0,r.wy)((0,r._)("input",{type:"text",class:"sm","onUpdate:modelValue":t[0]||(t[0]=e=>d.filter=e),placeholder:"filter"},null,512),[[a.nr,d.filter]])]),(0,r.Wm)(_,{modelValue:h.showCompleted,"onUpdate:modelValue":t[1]||(t[1]=e=>h.showCompleted=e),label:"Show completed"},null,8,["modelValue"])])]),(0,r.Wm)(f,{data:h.filteredData,fields:h.selectedFields,"filter-term":d.filter,"row-click-handler":h.goToApplication,"row-class":"cursor-pointer",sort:d.sort,"onUpdate:sort":t[2]||(t[2]=e=>d.sort=e),style:(0,n.j5)(h.remainingHeight),class:"overflow-auto text-xs",ref:"table"},{"cell-contacts":(0,r.w5)((({item:e})=>[(0,r._)("ul",null,[((0,r.wg)(!0),(0,r.iD)(r.HY,null,(0,r.Ko)(e.group.contacts,(e=>((0,r.wg)(),(0,r.iD)("li",{key:e.id},[(0,r._)("small",null,[(0,r._)("a",{href:`mailto:${e.person.email}`,class:"text-blue-500"},(0,n.zw)(e.person.name),9,i)])])))),128))])])),"cell-latest_log_entry_description":(0,r.w5)((({value:e})=>[(0,r._)("div",{innerHTML:e},null,8,u)])),"cell-latest_pending_next_action_entry":(0,r.w5)((({value:e})=>[(0,r._)("div",{innerHTML:e},null,8,c)])),_:1},8,["data","fields","filter-term","row-click-handler","sort","style"])])}l(6699);var m=l(3907),h=l(2790),_={name:"SummaryVceps",components:{},props:{expert_panel_type_id:{type:Number,default:null}},data(){return{fields:[{name:"name",label:"Name",type:String,sortable:!0},{name:"group.parent.name",label:"CDWG",type:String,sortable:!0,resolveValue(e){return e.group&&e.group.parent?e.group.parent.name:""}},{name:"current_step",label:"Step",type:Number,sortable:!0,resolveValue(e){return e.isCompleted?"Completed":e.current_step},resolveSort(e){return e.isCompleted?5:e.current_step}},{name:"contacts",label:"Contacts",type:Array,sortable:!1,class:["min-w-40"],step:1},{name:"step_1_received_date",label:2==this.expert_panel_type_id?"Step 1 Received":"Application Received",type:Date,sortable:!0,class:["min-w-28"],step:1},{name:"step_1_approval_date",label:2==this.expert_panel_type_id?"Step 1 Approved":"Application Approved",type:Date,sortable:!0,class:["min-w-28"],step:1},{name:"step_2_approval_date",label:"Step 2 Approved",type:Date,sortable:!0,class:["min-w-28"],step:2},{name:"step_3_approval_date",label:"Step 3 Approved",type:Date,sortable:!0,class:["min-w-28"],step:3},{name:"step_4_received_date",label:"Step 4 Received",type:Date,sortable:!0,class:["min-w-28"],step:4},{name:"step_4_approval_date",label:"Step 4 Approved",type:Date,sortable:!0,class:["min-w-28"],step:4}]}},computed:{...(0,m.Se)({applications:"applications/all"}),filteredData(){return this.applications.filter((e=>!this.expert_panel_type_id||e.expert_panel_type_id==this.expert_panel_type_id)).filter((e=>!!this.showCompleted||null==e.date_completed))},showCompleted:{set(e){let t=this.$route.query,l=this.$route.path,r={...t};e?r={...t,"show-completed":1}:delete r["show-completed"],this.$router.replace({path:l,query:r})},get(){return Boolean(parseInt(this.$route.query["show-completed"]))},immediate:!0},selectedFields(){const e=2==this.expert_panel_type_id?[1,2,3,4]:[1];return this.fields.filter((t=>!t.step||e.includes(t.step)))},showAllInfo:{immediate:!0,get(){return Object.keys(this.$route.query).includes("showAllInfo")?this.$route.query.showAllInfo:0},set(e){const t={...this.$route.query};t.showAllInfo=e,this.$router.replace({path:this.$route.path,query:t})}},remainingHeight(){return{height:"calc(100vh - 220px)"}}},methods:{getApplications(){const e={with:["group","group.parent","group.latestLogEntry","latestPendingNextAction","type","group.contacts","group.contacts.person"]},t={};Object.keys(t).length>0&&(e.where=t),this.$store.dispatch("applications/getApplications",e)},goToApplication(e){this.$router.push({name:"ApplicationDetail",params:{uuid:e.uuid}})}},mounted(){this.getApplications()},setup(){const{sort:e,filter:t}=(0,h.Z)();return{sort:e,filter:t}}},f=l(3744);const y=(0,f.Z)(_,[["render",d]]);var b=y},7332:function(e,t,l){l.r(t),l.d(t,{default:function(){return i}});var r=l(6252);function a(e,t,l,a,n,s){const p=(0,r.up)("applications-summary-table");return(0,r.wg)(),(0,r.iD)("div",null,[(0,r.Wm)(p,{"ep-type-id":1})])}var n=l(9197),s={name:"GcepsSummary",components:{ApplicationsSummaryTable:n.Z}},p=l(3744);const o=(0,p.Z)(s,[["render",a]]);var i=o},4192:function(e,t,l){l.r(t),l.d(t,{default:function(){return i}});var r=l(6252);function a(e,t,l,a,n,s){const p=(0,r.up)("applications-summary-table");return(0,r.wg)(),(0,r.iD)("div",null,[(0,r.Wm)(p,{"ep-type-id":2})])}var n=l(9197),s={name:"GcepsSummary",components:{ApplicationsSummaryTable:n.Z}},p=l(3744);const o=(0,p.Z)(s,[["render",a]]);var i=o}}]);
//# sourceMappingURL=application-summary.b1c52cb8.js.map