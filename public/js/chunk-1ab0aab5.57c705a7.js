(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-1ab0aab5"],{"223f":function(e,t,r){"use strict";r.r(t);r("4de4");var o=r("7a23"),c={class:"block mb-2",for:"filter-input"},n=Object(o["createTextVNode"])("Filter: ");function i(e,t,r,i,a,l){var u=this,s=Object(o["resolveComponent"])("data-table"),d=Object(o["resolveComponent"])("card");return Object(o["openBlock"])(),Object(o["createBlock"])(d,{title:"People"},{default:Object(o["withCtx"])((function(){return[Object(o["createVNode"])("label",c,[n,Object(o["withDirectives"])(Object(o["createVNode"])("input",{type:"text","onUpdate:modelValue":t[1]||(t[1]=function(t){return e.filter=t}),placeholder:"filter"},null,512),[[o["vModelText"],e.filter]])]),Object(o["createVNode"])(s,{fields:a.fields,data:u.people,class:"width-full","filter-term":e.filter,"row-click-handler":l.goToPerson,"row-class":"cursor-pointer",sort:e.sort,"onUpdate:sort":t[2]||(t[2]=function(t){return e.sort=t})},{"cell-uuid":Object(o["withCtx"])((function(e){return[Object(o["createVNode"])("button",{class:"btn btn-xs",onClick:Object(o["withModifiers"])((function(t){return l.goToEditPerson(e.item)}),["stop"])}," Edit ",8,["onClick"])]})),_:1},8,["fields","data","filter-term","row-click-handler","sort"])]})),_:1})}var a=r("5530"),l=r("5502"),u=r("5257"),s=[{name:"name",sortable:!0,type:String},{name:"email",sortable:!0,type:String},{name:"uuid",label:"",sortable:!1}],d={components:{},props:{},data:function(){return{fields:s}},computed:Object(a["a"])({},Object(l["b"])({people:"people/all"})),methods:{goToPerson:function(e){this.$router.push("/people/"+e.uuid)},goToEditPerson:function(e){this.$router.push("/people/".concat(e.uuid,"/edit"))}},mounted:function(){this.$store.dispatch("people/all",{})},setup:function(){return Object(u["a"])()}};d.render=i;t["default"]=d},5257:function(e,t,r){"use strict";r("4de4"),r("caad"),r("b64b"),r("ac1f"),r("2532"),r("5319");var o=r("5530"),c=r("6c02"),n=r("7a23");t["a"]=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null,t=Object(c["d"])(),r=Object(c["c"])();e=e||{field:"name",desc:!1};var i=Object(n["computed"])({immediate:!0,get:function(){return Object.keys(r.query).includes("sort-field")?{field:r.query["sort-field"],desc:Boolean(parseInt(r.query["sort-desc"]))}:e},set:function(e){var c={"sort-field":e.field,"sort-desc":e.desc?1:0},n=Object(o["a"])(Object(o["a"])({},r.query),c);t.replace({path:r.path,query:n})}}),a=Object(n["computed"])({set:function(e){var c=r.query,n=r.path,i=Object(o["a"])({},c);e?i=Object(o["a"])(Object(o["a"])({},c),{filter:e}):delete i.filter,t.replace({path:n,query:i})},get:function(){return r.query.filter},immediate:!0});return{sort:i,filter:a}}}}]);
//# sourceMappingURL=chunk-1ab0aab5.57c705a7.js.map