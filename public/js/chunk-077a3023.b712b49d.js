(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-077a3023","chunk-ce03ae04"],{"0d14":function(e,t,n){"use strict";n("f7f8")},4848:function(e,t,n){"use strict";n.r(t);n("b0c0");var r=n("7a23"),o={key:0},i=Object(r["createTextVNode"])(" We can't seem to find your membership id. Please try refreshing. "),a=Object(r["createTextVNode"])(" Review ClinGen’s "),c={class:"relative"},s=["onUpdate:modelValue","name"],l={key:1},u=["value","name","onUpdate:modelValue"],d=["onUpdate:modelValue","name"],p=Object(r["createTextVNode"])(" Saving... ");function m(e,t,n,m,b,f){var v=Object(r["resolveComponent"])("card"),h=Object(r["resolveComponent"])("coi-policy-link"),O=Object(r["resolveComponent"])("input-row"),j=Object(r["resolveComponent"])("button-row"),y=Object(r["resolveComponent"])("note");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[f.codeIsValid?Object(r["createCommentVNode"])("",!0):(Object(r["openBlock"])(),Object(r["createBlock"])(v,{key:0,title:b.verifying?"Loading COI Form":"COI Form not found"},{default:Object(r["withCtx"])((function(){return[b.verifying?Object(r["createCommentVNode"])("",!0):(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",o,"We couldn't find this COI."))]})),_:1},8,["title"])),f.groupMemberId?Object(r["createCommentVNode"])("",!0):(Object(r["openBlock"])(),Object(r["createBlock"])(v,{key:1,title:"There's a problem"},{default:Object(r["withCtx"])((function(){return[i]})),_:1})),f.codeIsValid?(Object(r["openBlock"])(),Object(r["createBlock"])(v,{key:2,title:f.coiTitle,class:"max-w-xl mx-auto relative"},{default:Object(r["withCtx"])((function(){return[Object(r["createElementVNode"])("p",null,[a,Object(r["createVNode"])(h)]),Object(r["createElementVNode"])("div",c,[(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(b.survey.questions,(function(e){return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",{key:e.name,class:Object(r["normalizeClass"])(e.class)},[Object(r["createVNode"])(r["Transition"],{name:"slide-fade-down"},{default:Object(r["withCtx"])((function(){return[Object(r["withDirectives"])(Object(r["createVNode"])(O,{label:e.question_text,errors:b.errors[e.name],vertical:!0},{default:Object(r["withCtx"])((function(){return["text"==e.type?Object(r["withDirectives"])((Object(r["openBlock"])(),Object(r["createElementBlock"])("textarea",{key:0,class:"w-full h-24","onUpdate:modelValue":function(t){return b.response[e.name]=t},name:e.name},null,8,s)),[[r["vModelText"],b.response[e.name]]]):Object(r["createCommentVNode"])("",!0),"multiple-choice"==e.type?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",l,[(Object(r["openBlock"])(!0),Object(r["createElementBlock"])(r["Fragment"],null,Object(r["renderList"])(e.options,(function(t){return Object(r["openBlock"])(),Object(r["createElementBlock"])("label",{key:t.value,class:"mb-1"},[Object(r["withDirectives"])(Object(r["createElementVNode"])("input",{type:"radio",value:t.value,name:e.name,"onUpdate:modelValue":function(t){return b.response[e.name]=t}},null,8,u),[[r["vModelRadio"],b.response[e.name]]]),Object(r["createElementVNode"])("div",null,Object(r["toDisplayString"])(t.label),1)])})),128))])):Object(r["createCommentVNode"])("",!0),"string"==e.type?Object(r["withDirectives"])((Object(r["openBlock"])(),Object(r["createElementBlock"])("input",{key:2,type:"text","onUpdate:modelValue":function(t){return b.response[e.name]=t},name:e.name},null,8,d)),[[r["vModelText"],b.response[e.name]]]):Object(r["createCommentVNode"])("",!0)]})),_:2},1032,["label","errors"]),[[r["vShow"],f.showQuestion(e)]])]})),_:2},1024)],2)})),128)),Object(r["createVNode"])(j,{"show-cancel":!1,onSubmitClicked:t[0]||(t[0]=function(e){return f.storeResponse()})},{default:Object(r["withCtx"])((function(){return[b.saving?Object(r["renderSlot"])(e.$slots,"default",{key:0},(function(){return[p]})):Object(r["createCommentVNode"])("",!0)]})),_:3})])]})),_:3},8,["title"])):Object(r["createCommentVNode"])("",!0),Object(r["createVNode"])(y,{class:"container"},{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])("GroupMemberId: "+Object(r["toDisplayString"])(f.groupMemberId),1)]})),_:1})])}var b=n("1da1"),f=(n("96cf"),n("7db0"),n("d3b7"),n("f0d7")),v=n("262e"),h=n("2caf"),O=n("bee2"),j=n("d4ec"),y=(n("d81d"),n("159b"),Object(O["a"])((function e(t){Object(j["a"])(this,e),this.question_text=t.question,this.name=t.name,this.options=t.options,this.validationRules=t.validation,this.type=t.type,this.show=t.show,this.class=t.class}))),w=function(e){Object(v["a"])(n,e);var t=Object(h["a"])(n);function n(e){var r;return Object(j["a"])(this,n),r=t.call(this,e),r.type="multiple-choice",r.options=[{label:"Yes",value:1},{label:"No",value:0}],r}return Object(O["a"])(n)}(y);function k(e){return"yes-no"==e.type?new w(e):new y(e)}var g=function(){function e(t){Object(j["a"])(this,e),this._name=t.name,this._questions=t.questions.map((function(e){return k(e)}))}return Object(O["a"])(e,[{key:"name",get:function(){return this._name}},{key:"questions",get:function(){return this._questions}},{key:"responseIsValid",value:function(e){return!0}},{key:"getResponseTemplate",value:function(){var e={};return this.questions.forEach((function(t){e[t.name]=null})),e}}]),e}(),x=g,V=n("f96b"),C=n("033d"),_=new x(f),N={props:{code:{required:!0,type:String}},data:function(){return{coiDef:f,survey:_,response:_.getResponseTemplate(),errors:{},epName:null,verifying:!1,saved:!1,saving:!1,redirectCountdown:5}},computed:{codeIsValid:function(){return null!==this.epName},coiTitle:function(){return _.name+" for "+this.epName},groupMemberId:function(){var e=this,t=this.$store.getters.currentUser.person.memberships.find((function(t){return t.group.expert_panel&&t.group.expert_panel.coi_code===e.code}));return t?t.id:null}},methods:{showQuestion:function(e){return!e.show||(Array.isArray(e.show.value)?e.show.value.indexOf(this.response[e.show.name])>-1:this.response[e.show.name]==e.show.value)},verifyCode:function(){var e=this;this.verifying=!0,V["a"].get("/api/coi/".concat(this.code,"/application")).then((function(t){e.epName=t.data.display_name})).then((function(){e.verifying=!1}))},storeResponse:function(){var e=this;return Object(b["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return e.saving=!0,t.prev=1,t.next=4,e.$store.dispatch("storeCoi",{code:e.code,groupMemberId:e.groupMemberId,coiData:e.response});case 4:e.saved=!0,e.$store.dispatch("forceGetCurrentUser"),e.$router.push({name:"Dashboard"}),t.next=12;break;case 9:t.prev=9,t.t0=t["catch"](1),Object(C["a"])(t.t0)?e.errors=t.t0.response.data.errors:e.$store.commit("pushError","You can not complete a COI for ".concat(e.epName," because you are not a member."));case 12:e.saving=!1;case 13:case"end":return t.stop()}}),t,null,[[1,9]])})))()}},mounted:function(){var e=this;return Object(b["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return e.verifyCode(),t.next=3,e.$store.dispatch("getCurrentUser");case 3:case"end":return t.stop()}}),t)})))()}},E=n("6b0d"),B=n.n(E);const S=B()(N,[["render",m]]);t["default"]=S},"48e8":function(e,t,n){"use strict";n.d(t,"c",(function(){return l})),n.d(t,"d",(function(){return u})),n.d(t,"a",(function(){return d})),n.d(t,"b",(function(){return p}));n("d3b7"),n("159b"),n("b64b"),n("d81d"),n("b0c0");var r=n("7a23"),o=n("f96b"),i=n("025e"),a=n("2297"),c=n("05b6"),s=n("526b"),l=Object(r["ref"])({countries:[],primaryOccupations:[],races:[],ethnicities:[],genders:[]}),u=Object(r["computed"])((function(){return[{name:"first_name"},{name:"last_name"},{name:"email"},{name:"institution_id",label:"Institution",type:"component",component:c["default"]},{name:"credentials",type:"large-text"},{name:"biography",type:"large-text"},{name:"*",type:"component",label:"Address",component:s["default"]},{name:"country_id",label:"Country",type:"select",options:l.value.countries},{name:"phone"},{name:"timezone",label:"Timezone",type:"component",component:a["default"]}]})),d=Object(r["computed"])((function(){return[{name:"primary_occupation_id",label:"Primary Occupation",type:"select",options:l.value.primaryOccupations},{name:"primary_occupation_other",label:" ",type:"text",placeholder:"Details",show:function(e){return 100==e.primary_occupation_id}},{name:"race_id",label:"Race",type:"select",options:l.value.races},{name:"race_other",label:" ",type:"text",placeholder:"Details",show:function(e){return 100==e.race_id}},{name:"ethnicity_id",label:"Ethnicity",type:"select",options:l.value.ethnicities},{name:"enthnicity_other",label:" ",type:"text",placeholder:"Details",show:function(e){return 100==e.enthnicity_id}},{name:"gender_id",label:"Gender",type:"select",options:l.value.genders},{name:"gender_other",label:" ",type:"text",placeholder:"Details",show:function(e){return 100==e.gender_id}}]})),p=function(){Object.keys(l.value).forEach((function(e){o["a"].get("/api/people/lookups/".concat(Object(i["c"])(e))).then((function(t){l.value[e]=t.data.data.map((function(e){return{value:e.id,label:e.name}}))})).catch((function(e){return console.error(e)}))}))}},"8d23":function(e,t,n){"use strict";n.r(t);var r=n("7a23"),o={class:"flex justify-center"},i=Object(r["createTextVNode"])("< Log In");function a(e,t,n,a,c,s){var l=Object(r["resolveComponent"])("router-link");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createElementVNode"])("div",o,[Object(r["createElementVNode"])("div",{class:"onboarding-container",style:Object(r["normalizeStyle"])("width: ".concat(s.currentStepWidth))},[(Object(r["openBlock"])(),Object(r["createBlock"])(r["KeepAlive"],null,[Object(r["createVNode"])(r["Transition"],{name:c.animationDirection,mode:"out-in"},{default:Object(r["withCtx"])((function(){return[(Object(r["openBlock"])(),Object(r["createBlock"])(Object(r["resolveDynamicComponent"])(s.currentStepComponent),{invite:c.invite,person:c.invite.person,code:c.invite.coi_code,ref:"stepForm",onCodeverified:s.handleCodeVerified,onOk:s.goForward,onSaved:s.goForward,onBack:s.goBack},null,8,["invite","person","code","onCodeverified","onOk","onSaved","onBack"]))]})),_:1},8,["name"])],1024))],4),Object(r["createElementVNode"])("p",null,[this.$store.getters.isAuthed?Object(r["createCommentVNode"])("",!0):(Object(r["openBlock"])(),Object(r["createBlock"])(l,{key:0,class:"block link pt-2",to:{name:"login"}},{default:Object(r["withCtx"])((function(){return[i]})),_:1}))])])])}n("b0c0");var c={class:"text-lg text-center font-bold"},s=Object(r["createElementVNode"])("br",null,null,-1),l=Object(r["createTextVNode"])(" You've been invited to join ClinGen"),u={key:0},d={key:1},p=Object(r["createElementVNode"])("p",null,"There are just a few steps to get you set up:",-1),m={class:"list-decimal pl-8"},b=Object(r["createElementVNode"])("li",null,"Create an account.",-1),f=Object(r["createElementVNode"])("li",null,"Fill out your profile.",-1),v=Object(r["createElementVNode"])("li",null,"Share some demographic information.",-1),h={key:0};function O(e,t,n,o,i,a){return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[Object(r["createElementVNode"])("p",c,[Object(r["createTextVNode"])(" Hi "+Object(r["toDisplayString"])(n.invite.first_name)+". ",1),s,l,n.invite.inviter?(Object(r["openBlock"])(),Object(r["createElementBlock"])("span",u," as part of the "+Object(r["toDisplayString"])(n.invite.inviter.name)+" "+Object(r["toDisplayString"])(n.invite.inviter.type.name.toUpperCase())+".",1)):(Object(r["openBlock"])(),Object(r["createElementBlock"])("span",d,"."))]),p,Object(r["createElementVNode"])("ol",m,[b,f,v,n.invite.inviter?(Object(r["openBlock"])(),Object(r["createElementBlock"])("li",h,"Complete a Conflict of Interest Survey.")):Object(r["createCommentVNode"])("",!0)]),Object(r["createElementVNode"])("p",null,[Object(r["createElementVNode"])("button",{class:"btn btn-lg blue w-full mt-4",onClick:t[0]||(t[0]=function(t){return e.$emit("ok")})}," Get Started ")])])}var j={name:"OnboardingSteps",props:{invite:{type:Object,required:!0}},emits:["ok"],data:function(){return{}},computed:{},methods:{}},y=n("6b0d"),w=n.n(y);const k=w()(j,[["render",O]]);var g=k,x={class:"w-64 mx-auto"},V=Object(r["createElementVNode"])("label",{for:"invite-code-input",class:"text-lg block"}," Enter your registration code: ",-1);function C(e,t,n,o,i,a){var c=Object(r["resolveComponent"])("input-errors");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",x,[V,Object(r["withDirectives"])(Object(r["createElementVNode"])("input",{type:"text",id:"invite-code-input","onUpdate:modelValue":t[0]||(t[0]=function(e){return o.inviteCode=e}),placeholder:"XXXXXXXXXX",class:"w-full"},null,512),[[r["vModelText"],o.inviteCode]]),Object(r["createVNode"])(c,{errors:o.errors},null,8,["errors"]),Object(r["createElementVNode"])("button",{class:"btn blue btn-lg block mt-2 w-full",onClick:t[1]||(t[1]=function(){return o.checkInvite&&o.checkInvite.apply(o,arguments)})}," Submit ")])}var _=n("1da1"),N=(n("ac1f"),n("5319"),n("96cf"),n("f96b")),E=function(){var e=Object(_["a"])(regeneratorRuntime.mark((function e(t){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.next=2,N["a"].get("/api/people/invites/".concat(t)).then((function(e){return e.data.data}));case 2:return e.abrupt("return",e.sent);case 3:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}(),B=function(){var e=Object(_["a"])(regeneratorRuntime.mark((function e(t,n,r,o){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.abrupt("return",N["a"].put("/api/people/invites/".concat(t.code),{email:n,password:r,password_confirmation:o}));case 1:case"end":return e.stop()}}),e)})));return function(t,n,r,o){return e.apply(this,arguments)}}(),S=function(){var e=Object(_["a"])(regeneratorRuntime.mark((function e(t){return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.abrupt("return",N["a"].put("/api/people/existing-user/invites/".concat(t.code)));case 1:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}(),q=n("c20e"),I=n("033d"),R={name:"InviteRedemptionCode",components:{InputErrors:q["default"]},props:{code:{required:!1},invite:{type:Object,required:!1,default:function(){return{}}}},data:function(){return{}},setup:function(e,t){var n=Object(r["toRefs"])(e),o=n.invite,i=Object(r["ref"])(null),a=Object(r["ref"])([]),c=function(){i.value=e.invite.code};Object(r["watch"])(o,(function(){c()}),{deep:!0}),Object(r["watch"])(i,(function(){a.value=[]}));var s=function(){var e=Object(_["a"])(regeneratorRuntime.mark((function e(){var n;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.prev=0,e.next=3,E(i.value);case 3:n=e.sent,t.emit("codeverified",n),e.next=13;break;case 7:if(e.prev=7,e.t0=e["catch"](0),404!=e.t0.response.status){e.next=12;break}return a.value=["The code you entered is not valid"],e.abrupt("return");case 12:Object(I["a"])(e.t0)&&(a.value=e.t0.response.data.errors.code);case 13:case"end":return e.stop()}}),e,null,[[0,7]])})));return function(){return e.apply(this,arguments)}}();return{inviteCode:i,errors:a,checkInvite:s,syncCode:c}},computed:{},methods:{},mounted:function(){this.syncCode()},beforeMount:function(){null!==this.$store.getters.currentUser.id&&(this.$store.commit("pushError","You can't redeem an invite b/c you're already logged in."),this.$router.replace({name:"Dashboard"}))}};const D=w()(R,[["render",C]]);var T=D,$=Object(r["createElementVNode"])("pre",null,null,-1),U={key:0},F={key:1},P=Object(r["createElementVNode"])("p",{class:"text-lg"},"Create your account",-1),A={class:"flex flex-row-reverse"};function M(e,t,n,o,i,a){var c=Object(r["resolveComponent"])("login-form"),s=Object(r["resolveComponent"])("input-row");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[$,n.invite.person.user_id?(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",U,[Object(r["createVNode"])(c,{onAuthenticated:o.redeemForExistingUser},null,8,["onAuthenticated"])])):(Object(r["openBlock"])(),Object(r["createElementBlock"])("div",F,[P,Object(r["createVNode"])(s,{label:"Email",modelValue:o.email,"onUpdate:modelValue":t[0]||(t[0]=function(e){return o.email=e}),errors:o.errors.email,"label-width-class":"w-24"},null,8,["modelValue","errors"]),Object(r["createVNode"])(s,{label:"Password",modelValue:o.password,"onUpdate:modelValue":t[1]||(t[1]=function(e){return o.password=e}),type:"password",errors:o.errors.password,"label-width-class":"w-24"},null,8,["modelValue","errors"]),Object(r["createVNode"])(s,{label:"Confirm Password",modelValue:o.password_confirmation,"onUpdate:modelValue":t[2]||(t[2]=function(e){return o.password_confirmation=e}),type:"password",errors:o.errors.password_confirmation,"label-width-class":"w-24"},null,8,["modelValue","errors"]),Object(r["createElementVNode"])("div",A,[Object(r["createElementVNode"])("button",{class:"btn blue",onClick:t[3]||(t[3]=function(){return o.createAccount&&o.createAccount.apply(o,arguments)})},"Next")])]))])}var X=n("5502"),z=n("61b1"),G={name:"AccountCreationForm",components:{LoginForm:z["default"]},props:{invite:{type:Object,required:!0}},setup:function(e,t){var n=Object(X["d"])(),o=Object(r["ref"])({}),i=Object(r["ref"])(null),a=Object(r["ref"])(null),c=Object(r["ref"])(null),s=function(){var r=Object(_["a"])(regeneratorRuntime.mark((function r(){return regeneratorRuntime.wrap((function(r){while(1)switch(r.prev=r.next){case 0:return r.prev=0,r.next=3,B(e.invite,i.value,a.value,c.value);case 3:return r.next=5,n.dispatch("login",{email:i.value,password:a.value});case 5:t.emit("saved"),r.next=11;break;case 8:r.prev=8,r.t0=r["catch"](0),Object(I["a"])(r.t0)&&(o.value=r.t0.response.data);case 11:case"end":return r.stop()}}),r,null,[[0,8]])})));return function(){return r.apply(this,arguments)}}(),l=function(){var n=Object(_["a"])(regeneratorRuntime.mark((function n(){return regeneratorRuntime.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return n.prev=0,n.next=3,S(e.invite);case 3:t.emit("saved"),n.next=9;break;case 6:n.prev=6,n.t0=n["catch"](0),Object(I["a"])(n.t0)&&(o.value=n.t0.response.data);case 9:case"end":return n.stop()}}),n,null,[[0,6]])})));return function(){return n.apply(this,arguments)}}(),u=function(){i.value=e.invite.person.email};return Object(r["onMounted"])((function(){return u()})),{errors:o,email:i,password:a,password_confirmation:c,createAccount:s,redeemForExistingUser:l}}};const L=w()(G,[["render",M]]);var Y=L,W=Object(r["createElementVNode"])("p",{class:"text-lg font-bold"},"Please fill out your profile",-1),J={class:"flex flex-row-reverse justify-between"};function H(e,t,n,o,i,a){var c=Object(r["resolveComponent"])("data-form"),s=Object(r["resolveComponent"])("collapsible"),l=Object(r["resolveComponent"])("dev-component");return Object(r["openBlock"])(),Object(r["createElementBlock"])("div",null,[W,Object(r["createVNode"])(c,{fields:i.fields,modelValue:i.profile,"onUpdate:modelValue":t[0]||(t[0]=function(e){return i.profile=e}),errors:i.errors},null,8,["fields","modelValue","errors"]),Object(r["createElementVNode"])("div",J,[Object(r["createElementVNode"])("button",{class:"btn blue",onClick:t[1]||(t[1]=function(){return a.goToNext&&a.goToNext.apply(a,arguments)})},"Next >")]),Object(r["createVNode"])(l,{class:"mt-4"},{default:Object(r["withCtx"])((function(){return[Object(r["createVNode"])(s,null,{default:Object(r["withCtx"])((function(){return[Object(r["createTextVNode"])(Object(r["toDisplayString"])(n.person),1)]})),_:1})]})),_:1})])}var Q=n("5530"),K=n("48e8"),Z={name:"ProfileForm",props:{person:{type:Object,required:!0}},data:function(){return{errors:{},profile:{},page:"profile",fields:K["d"]}},methods:{initProfile:function(){this.profile=Object(Q["a"])({},this.$store.getters["people/currentItem"].attributes)},save:function(){var e=this;return Object(_["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,e.$store.dispatch("people/updateProfile",{uuid:e.person.uuid,attributes:e.profile});case 3:return t.next=5,e.$store.dispatch("forceGetCurrentUser",{force:!0});case 5:e.$emit("saved"),t.next=11;break;case 8:t.prev=8,t.t0=t["catch"](0),Object(I["a"])(t.t0)&&(e.errors=t.t0.response.data.errors);case 11:case"end":return t.stop()}}),t,null,[[0,8]])})))()},goToNext:function(){this.validateProfile()&&this.save()},validateProfile:function(){this.errors={};var e=!0;return this.profile.institution_id||(this.errors.institution_id=["This field is required."],e=!1),this.profile.timezone||(this.errors.timezone=["This field is required"],e=!1),e}},setup:function(){return Object(r["onMounted"])((function(){return Object(K["b"])()})),{profileFields:K["d"],getLookups:K["b"]}},mounted:function(){var e=this;return Object(_["a"])(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,e.$store.dispatch("people/getPerson",{uuid:e.person.uuid});case 2:e.initProfile();case 3:case"end":return t.stop()}}),t)})))()}};const ee=w()(Z,[["render",H]]);var te=ee,ne=n("da07"),re=n("4848"),oe=[T,g,Y,te],ie=["20rem","20rem","20rem","40rem","40rem"],ae={name:"OverviewWizard",components:{InviteRedemptionForm:T,OnboardingSteps:g,AccountCreationForm:Y,ProfileForm:te},props:{code:{required:!1}},data:function(){return{animationDirection:"forward",currentStepIdx:0,invite:{person:new ne["a"]({})}}},computed:{currentStepComponent:function(){return oe[this.currentStepIdx]},currentStepWidth:function(){return ie[this.currentStepIdx]},canContinue:function(){return!this.$store.getters.isAuthed||this.$store.getters.isAuthed&&!this.$store.getters.currentUser.person.timezone}},watch:{invite:{deep:!0,handler:function(e){e.inviter&&oe.push(re["default"])}},code:{immediate:!0,handler:function(e){this.invite.code=e}}},methods:{handleCodeVerified:function(e){this.invite=e,this.goForward()},goBack:function(){this.animationDirection="fade",0!=this.currentStepIdx&&(this.currentStepIdx-=1)},goForward:function(){this.animationDirection="fade",this.currentStepIdx!=oe.length-1?this.currentStepIdx+=1:this.$router.push({name:"Dashboard"})},selectStep:function(){if(!this.invite.id)return this.currentStepIdx=0,void(this.$route.query.code&&(this.invite.code=this.$route.query.code));this.invite.id&&this.$store.state.user.id&&(this.currentStepIndex=3)}}};n("0d14");const ce=w()(ae,[["render",a],["__scopeId","data-v-531c3d28"]]);t["default"]=ce},f0d7:function(e){e.exports=JSON.parse('{"name":"Conflict of Interest Survey","questions":[{"name":"work_fee_lab","question":"Do you work for a laboratory that offers fee-for-service testing related to the work of your Expert Panel?","validation":"required","type":"yes-no"},{"name":"contributions_to_gd_in_ep","question":"Have you made substantial contributions to the literature implicating a gene:disease relationship that relates to the work of your Expert Panel?","validation":"required","type":"yes-no"},{"name":"contributions_to_genes","question":"Please list the genes:","type":"text","validation":"required_if:contributions_to_gd_in_ep,1","show":{"name":"contributions_to_gd_in_ep","value":1},"class":"ml-4"},{"name":"independent_efforts","question":"Do you have any other existing or planned independent efforts that will potentially overlap with the scope of your ClinGen work?","type":"multiple-choice","options":[{"label":"Yes","value":1},{"label":"No","value":0},{"label":"Unsure","value":2}],"validation":"required"},{"name":"independent_efforts_details","question":"Please describe and also send an email describing the project(s) to the co-chairs and coordinator:","type":"text","valiation":"required_if:independent_efforts,1","show":{"name":"independent_efforts","value":[1,2]},"class":"ml-4"},{"name":"coi","question":"Do you have any other potential conflicts of interest to disclose (e.g. patents, intellectual property ownership, or paid consultancies related to any variants or genes associated with the work of your Expert Panel):","type":"multiple-choice","options":[{"label":"Yes","value":1},{"label":"No","value":0},{"label":"Unsure","value":2}],"validation":"required"},{"name":"coi_details","question":"Please describe:","type":"text","valiation":"required_if:coi,1","show":{"name":"coi","value":[1,2]},"class":"ml-4"}]}')},f7f8:function(e,t,n){}}]);
//# sourceMappingURL=chunk-077a3023.b712b49d.js.map