(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-2d0d43f3"],{"5fe1":function(e,n,t){"use strict";t.r(n);var r=t("7a23");function a(e,n,t,a,u,i){var o=Object(r["resolveComponent"])("annual-update-form");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[u.annualReview.expert_panel.group.uuid?(Object(r["openBlock"])(),Object(r["createBlock"])(o,{key:0,uuid:u.annualReview.expert_panel.group.uuid},null,8,["uuid"])):Object(r["createCommentVNode"])("",!0)])}var u=t("1da1"),i=(t("96cf"),t("db04")),o=t("4794"),c={name:"AnnualUpdateDetail",components:{AnnualUpdateForm:o["default"]},props:{id:{required:!0,type:String}},data:function(){return{annualReview:{data:{},submitter:{},expert_panel:{group:{members:[]}},window:{}},errors:{}}},computed:{expertPanel:function(){return this.annualReview.expert_panel||{}},window:function(){return this.annualReview.window||{}},submitter:function(){return this.submitter?this.submitter.person:{}}},methods:{getAnnualUpdate:function(){var e=this;return Object(u["a"])(regeneratorRuntime.mark((function n(){return regeneratorRuntime.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return n.next=2,i["a"].get("/api/annual-updates/".concat(e.id)).then((function(e){return e.data}));case 2:e.annualReview=n.sent;case 3:case"end":return n.stop()}}),n)})))()}},mounted:function(){var e=this;return Object(u["a"])(regeneratorRuntime.mark((function n(){return regeneratorRuntime.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return n.next=2,e.getAnnualUpdate();case 2:case"end":return n.stop()}}),n)})))()}},p=t("6b0d"),d=t.n(p);const s=d()(c,[["render",a]]);n["default"]=s}}]);
//# sourceMappingURL=chunk-2d0d43f3.eb628189.js.map