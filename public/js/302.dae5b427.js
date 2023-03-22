"use strict";(self["webpackChunkepam"]=self["webpackChunkepam"]||[]).push([[302],{2790:function(e,t,l){l.d(t,{Z:function(){return s}});l(6699);var a=l(2119),n=l(6252);function s(e=null){const t=(0,a.tv)(),l=(0,a.yj)();e||(console.log('Warning: defaultSort is deprecated.  Please provide a sort object: {field: "fieldname", desc: boolean}'),e=e||{field:"name",desc:!1});const s=(0,n.Fl)({immediate:!0,get(){return Object.keys(l.query).includes("sort-field")?{field:l.query["sort-field"],desc:Boolean(parseInt(l.query["sort-desc"]))}:e},set(e){const a={"sort-field":e.field,"sort-desc":e.desc?1:0},n={...l.query,...a};t.replace({path:l.path,query:n})}}),i=(0,n.Fl)({set(e){let a=l.query,n=l.path,s={...a};e?s={...a,filter:e}:delete s.filter,t.replace({path:n,query:s})},get(){return l.query.filter},immediate:!0});return{sort:s,filter:i}}},6302:function(e,t,l){l.r(t),l.d(t,{default:function(){return E}});var a=l(6252),n=l(3577),s=l(9963);const i={class:"flex justify-between items-center border-b mb-4"},d={class:"border-0 mb-0"},r=["value"],o={key:0},u={key:0};function c(e,t,l,c,p,m){const w=(0,a.up)("annual-update-table"),f=(0,a.up)("tab-item"),h=(0,a.up)("tabs-container");return(0,a.wg)(),(0,a.iD)("div",null,[(0,a._)("header",i,[(0,a._)("h1",d," Annual Updates for "+(0,n.zw)(m.selectedYear),1),(0,a.Uk)(" "+(0,n.zw)(e.formatDate(m.selectedStartDate))+" - "+(0,n.zw)(e.formatDate(m.selectedEndDate))+" ",1),p.windows.length>1?(0,a.wy)(((0,a.wg)(),(0,a.iD)("select",{key:0,"onUpdate:modelValue":t[0]||(t[0]=e=>p.selectedWindow=e),class:"font-normal text-md"},[((0,a.wg)(!0),(0,a.iD)(a.HY,null,(0,a.Ko)(m.sortedWindows,(e=>((0,a.wg)(),(0,a.iD)("option",{value:e,key:e.id},(0,n.zw)(e.for_year),9,r)))),128))],512)),[[s.bM,p.selectedWindow]]):(0,a.kq)("",!0)]),(0,a.Wm)(h,null,{default:(0,a.w5)((()=>[(0,a.Wm)(f,{label:"VCEPS"},{default:(0,a.w5)((()=>[p.loading?((0,a.wg)(),(0,a.iD)("div",o,"Loading…")):((0,a.wg)(),(0,a.j4)(w,{key:1,items:m.vcepReviews},null,8,["items"]))])),_:1}),(0,a.Wm)(f,{label:"GCEPS"},{default:(0,a.w5)((()=>[p.loading?((0,a.wg)(),(0,a.iD)("div",u,"Loading…")):((0,a.wg)(),(0,a.j4)(w,{key:1,items:m.gcepReviews},null,8,["items"]))])),_:1})])),_:1})])}var p=l(9028),m=l(6486);const w={class:"flex mb-2 items-end justify-between"},f={class:"flex space-x-4 items-end"},h=(0,a._)("option",{value:null},"Any",-1),b=(0,a._)("option",{value:2},"Only Pending",-1),y=(0,a._)("option",{value:1},"Only Completed",-1),_=[h,b,y],g=(0,a.Uk)(" view ");function v(e,t,l,n,i,d){const r=(0,a.up)("router-link"),o=(0,a.up)("data-table");return(0,a.wg)(),(0,a.iD)("div",null,[(0,a._)("div",w,[(0,a._)("div",f,[(0,a.wy)((0,a._)("input",{"onUpdate:modelValue":t[0]||(t[0]=e=>n.filter=e),placeholder:"EP name, submitter name",class:"w-60"},null,512),[[s.nr,n.filter]]),(0,a.wy)((0,a._)("select",{"onUpdate:modelValue":t[1]||(t[1]=e=>d.completedFilter=e),class:"flex radio-group"},_,512),[[s.bM,d.completedFilter]])]),(0,a._)("button",{class:"btn btn-xs",onClick:t[2]||(t[2]=(...e)=>d.exportData&&d.exportData(...e))},"Export Data")]),(0,a.Wm)(o,{data:d.filteredItems,fields:i.fields,sort:n.sort,"onUpdate:sort":t[3]||(t[3]=e=>n.sort=e)},{"cell-action":(0,a.w5)((({item:e})=>[(0,a.Wm)(r,{to:{name:"AnnualUpdateDetail",params:{id:e.id}}},{default:(0,a.w5)((()=>[g])),_:2},1032,["to"])])),_:1},8,["data","fields","sort"])])}var W=l(2790),x={name:"AnnualUpdateTeable",props:{items:{type:Array,required:!0}},data(){return{fields:[{name:"id",sortable:!0,label:"ID"},{name:"expert_panel.display_name",label:"Expert Panel",sortable:!0},{name:"submitter.person.name",label:"Submitter",sortable:!0},{name:"completed_at",label:"Completed",sortable:!0,resolveValue:e=>e.completed_at?this.formatDate(e.completed_at):null},{name:"action",label:"",sortable:!1}]}},computed:{completedFilter:{get:function(){return this.$route.query.completed||null},set:function(e){let t=this.$route.query,l=this.$route.path,a={...t};e?a={...t,completed:e}:delete a.completed,this.$router.replace({path:l,query:a})}},filteredItems(){let e=JSON.parse(JSON.stringify(this.items));if(null!=this.filter){const t=new RegExp(`.*${this.filter}.*`,"i");e=this.items.filter((e=>e.expert_panel.display_name.match(t)||e.submitter&&e.submitter.person&&e.submitter.person.name.match(t)))}return null!=this.completedFilter&&(e=e.filter((e=>1==this.completedFilter?null!==e.completed_at:null==e.completed_at))),e}},methods:{exportData(){const e=this.filteredItems.map((e=>e.id));p.hi.post("/api/annual-updates/export",{annual_update_ids:e}).then((e=>{const t=document.createElement("a");t.style.display="none",document.body.appendChild(t),t.href=window.URL.createObjectURL(new Blob([e.data,{type:"text/csv"}])),t.setAttribute("download","annual_updates.csv"),t.click(),document.body.removeChild(t)}))}},setup(){const{sort:e,filter:t}=(0,W.Z)({field:"expert_panel.display_name",desc:!1});return{sort:e,filter:t}}},D=l(3744);const k=(0,D.Z)(x,[["render",v]]);var q=k,U={name:"AnnualUpdatesList",components:{AnnualUpdateTable:q},data(){return{selectedWindow:null,windows:[],items:[],loading:!1}},computed:{sortedWindows(){return(0,m.orderBy)(this.windows,"for_year","desc")},latestWindow(){return this.sortedWindows[0]||{}},gcepReviews(){return this.items.filter((e=>1==e.expert_panel.expert_panel_type_id))},vcepReviews(){return this.items.filter((e=>2==e.expert_panel.expert_panel_type_id))},selectedYear(){return this.selectedWindow?this.selectedWindow.for_year:this.latestWindow.for_year},selectedStartDate(){return this.selectedWindow?this.selectedWindow.start:this.latestWindow.start},selectedEndDate(){return this.selectedWindow?this.selectedWindow.end:this.latestWindow.end}},watch:{selectedWindow(e){e&&this.getItems()}},methods:{async getWindows(){this.windows=await p.hi.get("/api/annual-updates/windows").then((e=>e.data)),this.selectedWindow=this.latestWindow},async getItems(){let e="/api/annual-updates";this.selectedWindow&&(e+=`?window_id=${this.selectedWindow.id}`),this.loading=!0,this.items=await p.hi.get(e).then((e=>e.data)),this.loading=!1}},mounted(){this.getWindows(),this.getItems()}};const C=(0,D.Z)(U,[["render",c],["__scopeId","data-v-40a5b7f2"]]);var E=C}}]);
//# sourceMappingURL=302.dae5b427.js.map