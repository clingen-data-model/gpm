"use strict";(self["webpackChunkepam"]=self["webpackChunkepam"]||[]).push([[847,206],{7206:function(e,t,n){n.r(t),n.d(t,{default:function(){return S}});var o=n(6252),i=n(3577),r=n(9963);const s={key:0},a=(0,o.Uk)(" We can't seem to find your membership id. Please try refreshing. "),l=(0,o.Uk)(" Review ClinGen’s "),u={class:"relative"},d=["onUpdate:modelValue","name"],c={key:1},p=["value","name","onUpdate:modelValue"],m=["onUpdate:modelValue","name"],h=(0,o.Uk)(" Saving... ");function v(e,t,n,v,w,f){const y=(0,o.up)("card"),g=(0,o.up)("coi-policy-link"),b=(0,o.up)("input-row"),_=(0,o.up)("button-row"),k=(0,o.up)("note");return(0,o.wg)(),(0,o.iD)("div",null,[(0,o._)("pre",null,(0,i.zw)(e.lastCoiResponse),1),f.codeIsValid?(0,o.kq)("",!0):((0,o.wg)(),(0,o.j4)(y,{key:0,title:w.verifying?"Loading COI Form":"COI Form not found"},{default:(0,o.w5)((()=>[w.verifying?(0,o.kq)("",!0):((0,o.wg)(),(0,o.iD)("div",s,"We couldn't find this COI."))])),_:1},8,["title"])),f.groupMemberId?(0,o.kq)("",!0):((0,o.wg)(),(0,o.j4)(y,{key:1,title:"There's a problem"},{default:(0,o.w5)((()=>[a])),_:1})),f.codeIsValid?((0,o.wg)(),(0,o.j4)(y,{key:2,title:f.coiTitle,class:"max-w-xl mx-auto relative"},{default:(0,o.w5)((()=>[(0,o._)("p",null,[l,(0,o.Wm)(g)]),(0,o._)("div",u,[((0,o.wg)(!0),(0,o.iD)(o.HY,null,(0,o.Ko)(w.survey.questions,(e=>((0,o.wg)(),(0,o.iD)("div",{key:e.name,class:(0,i.C_)(e.class)},[(0,o.Wm)(r.uT,{name:"slide-fade-down"},{default:(0,o.w5)((()=>[(0,o.wy)((0,o.Wm)(b,{label:e.question_text,errors:w.errors[e.name],vertical:!0},{default:(0,o.w5)((()=>["text"==e.type?(0,o.wy)(((0,o.wg)(),(0,o.iD)("textarea",{key:0,class:"w-full h-24","onUpdate:modelValue":t=>w.response[e.name]=t,name:e.name},null,8,d)),[[r.nr,w.response[e.name]]]):(0,o.kq)("",!0),"multiple-choice"==e.type?((0,o.wg)(),(0,o.iD)("div",c,[((0,o.wg)(!0),(0,o.iD)(o.HY,null,(0,o.Ko)(e.options,(t=>((0,o.wg)(),(0,o.iD)("label",{key:t.value,class:"mb-1"},[(0,o.wy)((0,o._)("input",{type:"radio",value:t.value,name:e.name,"onUpdate:modelValue":t=>w.response[e.name]=t},null,8,p),[[r.G2,w.response[e.name]]]),(0,o._)("div",null,(0,i.zw)(t.label),1)])))),128))])):(0,o.kq)("",!0),"string"==e.type?(0,o.wy)(((0,o.wg)(),(0,o.iD)("input",{key:2,type:"text","onUpdate:modelValue":t=>w.response[e.name]=t,name:e.name},null,8,m)),[[r.nr,w.response[e.name]]]):(0,o.kq)("",!0)])),_:2},1032,["label","errors"]),[[r.F8,f.showQuestion(e)]])])),_:2},1024)],2)))),128)),(0,o.Wm)(_,{"show-cancel":!1,onSubmitClicked:t[0]||(t[0]=e=>f.storeResponse())},{default:(0,o.w5)((()=>[w.saving?(0,o.WI)(e.$slots,"default",{key:0},(()=>[h])):(0,o.kq)("",!0)])),_:3})])])),_:3},8,["title"])):(0,o.kq)("",!0),(0,o.Wm)(k,{class:"container"},{default:(0,o.w5)((()=>[(0,o.Uk)("GroupMemberId: "+(0,i.zw)(f.groupMemberId),1)])),_:1})])}var w=JSON.parse('{"name":"Conflict of Interest Survey","questions":[{"name":"work_fee_lab","question":"Do you work for a laboratory that offers fee-for-service testing related to the work of your Expert Panel?","validation":"required","type":"yes-no"},{"name":"contributions_to_gd_in_ep","question":"Have you made substantial contributions to the literature implicating a gene:disease relationship that relates to the work of your Expert Panel?","validation":"required","type":"yes-no"},{"name":"contributions_to_genes","question":"Please list the genes:","type":"text","validation":"required_if:contributions_to_gd_in_ep,1","show":{"name":"contributions_to_gd_in_ep","value":1},"class":"ml-4"},{"name":"independent_efforts","question":"Do you have any other existing or planned independent efforts that will potentially overlap with the scope of your ClinGen work?","type":"multiple-choice","options":[{"label":"Yes","value":1},{"label":"No","value":0},{"label":"Unsure","value":2}],"validation":"required"},{"name":"independent_efforts_details","question":"Please describe and also send an email describing the project(s) to the co-chairs and coordinator:","type":"text","valiation":"required_if:independent_efforts,1","show":{"name":"independent_efforts","value":[1,2]},"class":"ml-4"},{"name":"coi","question":"Do you have any other potential conflicts of interest to disclose (e.g. patents, intellectual property ownership, or paid consultancies related to any variants or genes associated with the work of your Expert Panel):","type":"multiple-choice","options":[{"label":"Yes","value":1},{"label":"No","value":0},{"label":"Unsure","value":2}],"validation":"required"},{"name":"coi_details","question":"Please describe:","type":"text","valiation":"required_if:coi,1","show":{"name":"coi","value":[1,2]},"class":"ml-4"}]}');class f{constructor(e){this.question_text=e.question,this.name=e.name,this.options=e.options,this.validationRules=e.validation,this.type=e.type,this.show=e.show,this.class=e.class}}class y extends f{constructor(e){super(e),this.type="multiple-choice",this.options=[{label:"Yes",value:1},{label:"No",value:0}]}}function g(e){return"yes-no"==e.type?new y(e):new f(e)}class b{constructor(e){this._name=e.name,this._questions=e.questions.map((e=>g(e)))}get name(){return this._name}get questions(){return this._questions}responseIsValid(e){return!0}getResponseTemplate(){const e={};return this.questions.forEach((t=>{e[t.name]=null})),e}}var _=b,k=n(812),C=n(9700);const q=new _(w);var x={name:"Coi",props:{code:{required:!0,type:String}},data(){return{coiDef:w,survey:q,response:q.getResponseTemplate(),errors:{},epName:null,verifying:!1,saved:!1,saving:!1,redirectCountdown:5}},computed:{codeIsValid(){return null!==this.epName},coiTitle(){return q.name+" for "+this.epName},membership(){return this.$store.getters.currentUser.person.memberships.find((e=>e.group.expert_panel&&e.group.expert_panel.coi_code===this.code))},groupMemberId(){return this.membership?this.membership.id:null}},watch:{code:{immediate:!0,handler(){this.initResponseValues()}}},methods:{initResponseValues(){return this.membership&&this.membership.cois.length>0&&(this.response={...this.membership.cois[this.membership.cois.length-1].data}),{}},showQuestion(e){return!e.show||(Array.isArray(e.show.value)?e.show.value.indexOf(this.response[e.show.name])>-1:this.response[e.show.name]==e.show.value)},verifyCode(){this.verifying=!0,k.Z.get(`/api/coi/${this.code}/application`).then((e=>{this.epName=e.data.display_name})).then((()=>{this.verifying=!1}))},async storeResponse(){this.saving=!0;try{await this.$store.dispatch("storeCoi",{code:this.code,groupMemberId:this.groupMemberId,coiData:this.response}),this.saved=!0,await this.$store.dispatch("forceGetCurrentUser"),this.$router.push({name:"Dashboard"})}catch(e){(0,C.Z)(e)?this.errors=e.response.data.errors:this.$store.commit("pushError",`You can not complete a COI for ${this.epName} because you are not a member.`)}this.saving=!1}},async mounted(){this.verifyCode(),await this.$store.dispatch("getCurrentUser")}},I=n(3744);const D=(0,I.Z)(x,[["render",v]]);var S=D},7641:function(e,t,n){n.r(t),n.d(t,{default:function(){return re}});var o=n(6252),i=n(3577),r=n(9963);const s={class:"flex justify-center"},a=(0,o.Uk)("< Log In");function l(e,t,n,l,u,d){const c=(0,o.up)("router-link");return(0,o.wg)(),(0,o.iD)("div",null,[(0,o._)("div",s,[(0,o._)("div",{class:"onboarding-container",style:(0,i.j5)(`width: ${d.currentStepWidth}`)},[((0,o.wg)(),(0,o.j4)(o.Ob,null,[(0,o.Wm)(r.uT,{name:u.animationDirection,mode:"out-in"},{default:(0,o.w5)((()=>[((0,o.wg)(),(0,o.j4)((0,o.LL)(d.currentStepComponent),{invite:u.invite,person:u.invite.person,code:u.invite.coi_code,ref:"stepForm",onCodeverified:d.handleCodeVerified,onOk:d.goForward,onSaved:d.goForward,onBack:d.goBack},null,40,["invite","person","code","onCodeverified","onOk","onSaved","onBack"]))])),_:1},8,["name"])],1024))],4),(0,o._)("p",null,[this.$store.getters.isAuthed?(0,o.kq)("",!0):((0,o.wg)(),(0,o.j4)(c,{key:0,class:"block link pt-2",to:{name:"login"}},{default:(0,o.w5)((()=>[a])),_:1}))])])])}const u={class:"text-lg text-center font-bold"},d=(0,o._)("br",null,null,-1),c=(0,o.Uk)(" You've been invited to join ClinGen "),p={key:0},m={key:1},h=(0,o._)("p",null,"There are just a few steps to get you set up:",-1),v={class:"list-decimal pl-8"},w=(0,o._)("li",null,"Create an account.",-1),f=(0,o._)("li",null,"Fill out your profile.",-1),y=(0,o._)("li",null,"Share some demographic information.",-1),g={key:0};function b(e,t,n,r,s,a){return(0,o.wg)(),(0,o.iD)("div",null,[(0,o._)("p",u,[(0,o.Uk)(" Hi "+(0,i.zw)(n.invite.first_name)+". ",1),d,c,n.invite.inviter?((0,o.wg)(),(0,o.iD)("span",p," as part of the "+(0,i.zw)(n.invite.inviter.name)+" "+(0,i.zw)(n.invite.inviter.type.name.toUpperCase())+". ",1)):((0,o.wg)(),(0,o.iD)("span",m,"."))]),h,(0,o._)("ol",v,[w,f,y,n.invite.inviter?((0,o.wg)(),(0,o.iD)("li",g,"Complete a Conflict of Interest Survey.")):(0,o.kq)("",!0)]),(0,o._)("p",null,[(0,o._)("button",{class:"btn btn-lg blue w-full mt-4",onClick:t[0]||(t[0]=t=>e.$emit("ok"))}," Get Started ")])])}var _={name:"OnboardingSteps",props:{invite:{type:Object,required:!0}},emits:["ok"],data(){return{}},computed:{},methods:{}},k=n(3744);const C=(0,k.Z)(_,[["render",b]]);var q=C;const x={class:"w-64 mx-auto"},I=(0,o._)("label",{for:"invite-code-input",class:"text-lg block"}," Enter your registration code: ",-1);function D(e,t,n,i,s,a){const l=(0,o.up)("input-errors");return(0,o.wg)(),(0,o.iD)("div",x,[I,(0,o.wy)((0,o._)("input",{type:"text",id:"invite-code-input","onUpdate:modelValue":t[0]||(t[0]=e=>i.inviteCode=e),placeholder:"XXXXXXXXXX",class:"w-full"},null,512),[[r.nr,i.inviteCode]]),(0,o.Wm)(l,{errors:i.errors},null,8,["errors"]),(0,o._)("button",{class:"btn blue btn-lg block mt-2 w-full",onClick:t[1]||(t[1]=(...e)=>i.checkInvite&&i.checkInvite(...e))}," Submit ")])}var S=n(2262),U=n(9843),$=n(9370),V=n(9700),P={name:"InviteRedemptionCode",components:{InputErrors:$["default"]},props:{code:{required:!1},invite:{type:Object,required:!1,default:()=>({})}},data(){return{}},setup(e,t){const{invite:n}=(0,S.BK)(e);let i=(0,S.iH)(null),r=(0,S.iH)([]);const s=()=>{i.value=e.invite.code};(0,o.YP)(n,(()=>{s()}),{deep:!0}),(0,o.YP)(i,(()=>{r.value=[]}));const a=async()=>{try{const e=await(0,U.gc)(i.value);t.emit("codeverified",e)}catch(e){if(404==e.response.status)return void(r.value=["The code you entered is not valid"]);(0,V.Z)(e)&&(r.value=e.response.data.errors.code)}};return{inviteCode:i,errors:r,checkInvite:a,syncCode:s}},computed:{},methods:{},mounted(){this.syncCode()},beforeMount(){null!==this.$store.getters.currentUser.id&&(this.$store.commit("pushError","You can't redeem an invite b/c you're already logged in."),this.$router.replace({name:"Dashboard"}))}};const W=(0,k.Z)(P,[["render",D]]);var F=W;const O=(0,o._)("pre",null,null,-1),j={key:0},Z=(0,o.Uk)(" It looks like you've already activated you account. Please login to continue. "),A={key:1},R=(0,o._)("p",{class:"text-lg"},"Create your account",-1),E={class:"flex flex-row-reverse"};function N(e,t,n,i,r,s){const a=(0,o.up)("static-alert"),l=(0,o.up)("login-form"),u=(0,o.up)("input-row");return(0,o.wg)(),(0,o.iD)("div",null,[O,n.invite.person.user_id?((0,o.wg)(),(0,o.iD)("div",j,[(0,o.Wm)(a,null,{default:(0,o.w5)((()=>[Z])),_:1}),(0,o.Wm)(l,{onAuthenticated:i.redeemForExistingUser},null,8,["onAuthenticated"])])):((0,o.wg)(),(0,o.iD)("div",A,[R,(0,o.Wm)(u,{label:"Email",modelValue:i.email,"onUpdate:modelValue":t[0]||(t[0]=e=>i.email=e),errors:i.errors.email,"label-width-class":"w-24"},null,8,["modelValue","errors"]),(0,o.Wm)(u,{label:"Password",modelValue:i.password,"onUpdate:modelValue":t[1]||(t[1]=e=>i.password=e),type:"password",errors:i.errors.password,"label-width-class":"w-24"},null,8,["modelValue","errors"]),(0,o.Wm)(u,{label:"Confirm Password",modelValue:i.password_confirmation,"onUpdate:modelValue":t[2]||(t[2]=e=>i.password_confirmation=e),type:"password",errors:i.errors.password,"label-width-class":"w-24"},null,8,["modelValue","errors"]),(0,o._)("div",E,[(0,o._)("button",{class:"btn blue",onClick:t[3]||(t[3]=(...e)=>i.createAccount&&i.createAccount(...e))},"Next")])]))])}var T=n(3907),H=n(5973),X={name:"AccountCreationForm",components:{LoginForm:H["default"]},props:{invite:{type:Object,required:!0}},setup(e,t){const n=(0,T.oR)();let i=(0,S.iH)({}),r=(0,S.iH)(null),s=(0,S.iH)(null),a=(0,S.iH)(null);const l=async()=>{try{await(0,U.MJ)(e.invite,r.value,s.value,a.value),await n.dispatch("login",{email:r.value,password:s.value}),t.emit("saved")}catch(o){(0,V.Z)(o)&&(i.value=o.response.data.errors)}},u=async()=>{try{await(0,U.e$)(e.invite),t.emit("saved")}catch(n){(0,V.Z)(n)&&(i.value=n.response.data)}},d=()=>{r.value=e.invite.person.email};return(0,o.bv)((()=>d())),{errors:i,email:r,password:s,password_confirmation:a,createAccount:l,redeemForExistingUser:u}}};const Y=(0,k.Z)(X,[["render",N]]);var z=Y;const G=(0,o._)("p",{class:"text-lg font-bold"},"Please fill out your profile",-1);function M(e,t,n,r,s,a){const l=(0,o.up)("PeopleProfileForm"),u=(0,o.up)("collapsible"),d=(0,o.up)("dev-component");return(0,o.wg)(),(0,o.iD)("div",null,[G,(0,o.Wm)(l,{person:n.person,onSaved:a.handleSaved,allowCancel:!1,showTitle:!1,saveButtonText:"Next"},null,8,["person","onSaved"]),(0,o.Wm)(d,{class:"mt-4"},{default:(0,o.w5)((()=>[(0,o.Wm)(u,null,{default:(0,o.w5)((()=>[(0,o.Uk)((0,i.zw)(n.person),1)])),_:1})])),_:1})])}var B=n(1202),L=n(2349),K={name:"ProfileForm",components:{PeopleProfileForm:L.Z},props:{person:{type:Object,required:!0}},data(){return{errors:{},profile:{},page:"profile"}},methods:{initProfile(){this.profile={...this.$store.getters["people/currentItem"].attributes}},async handleSaved(){this.$store.dispatch("forceGetCurrentUser"),this.$emit("saved")}},setup(){(0,o.bv)((()=>(0,B.tV)()))},async mounted(){await this.$store.dispatch("people/getPerson",{uuid:this.person.uuid}),this.initProfile()}};const J=(0,k.Z)(K,[["render",M]]);var Q=J,ee=n(284);n(7206),n(8979);const te=[F,q,z],ne=["20rem","20rem","20rem","40rem","40rem"];var oe={name:"OverviewWizard",components:{InviteRedemptionForm:F,OnboardingSteps:q,AccountCreationForm:z,ProfileForm:Q},props:{code:{required:!1}},data(){return{animationDirection:"forward",currentStepIdx:0,invite:{person:new ee.Z({})}}},computed:{currentStepComponent(){return te[this.currentStepIdx]},currentStepWidth(){return ne[this.currentStepIdx]},canContinue(){return!this.$store.getters.isAuthed||this.$store.getters.isAuthed&&!this.$store.getters.currentUser.person.timezone}},watch:{code:{immediate:!0,handler:function(e){this.invite.code=e}}},methods:{handleCodeVerified(e){this.invite=e,this.goForward()},goBack(){this.animationDirection="fade",0!=this.currentStepIdx&&(this.currentStepIdx-=1)},goForward(){this.animationDirection="fade",this.currentStepIdx!=te.length-1?this.currentStepIdx+=1:this.$router.push({name:"Dashboard"})},selectStep(){if(!this.invite.id)return this.currentStepIdx=0,void(this.$route.query.code&&(this.invite.code=this.$route.query.code));this.invite.id&&this.$store.state.user.id&&(this.currentStepIndex=3)}}};const ie=(0,k.Z)(oe,[["render",l],["__scopeId","data-v-4c2a7a0d"]]);var re=ie}}]);
//# sourceMappingURL=847.ba6d871d.js.map