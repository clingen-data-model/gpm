"use strict";(self["webpackChunkepam"]=self["webpackChunkepam"]||[]).push([[105],{417:function(e,t,n){var a=n(2262),l=n(6252),o=n(3600);const r={internal:1,suggestion:2,requirement:3};t["Z"]=(e,t)=>{const n=(0,a.iH)([]),s=(0,l.Fl)((()=>n.value.filter((e=>!e.is_resolved)))),i=(0,l.Fl)((()=>s.value.filter((e=>e.comment_type_id==r.requirement)))),u=(0,l.Fl)((()=>s.value.filter((e=>e.comment_type_id==r.suggestion)))),m=(0,l.Fl)((()=>[...i.value,...u.value])),c=(0,l.Fl)((()=>s.value.filter((e=>e.comment_type_id==r.internal)))),d=e=>n.value.findIndex((t=>t.id==e.id)),p=async()=>{n.value=await o.Z.query({where:{subject_type:e,subject_id:t}})},v=e=>n.value.push(e),_=e=>n.value.splice(d(e),1),w=e=>n.value[d(e)]=e;return{subject:{type:e,id:t},comments:n,openComments:s,openRequirements:i,openSuggestions:u,openInternal:c,getComments:p,addComment:v,removeComment:_,updateComment:w,commentsForEp:m}}},6381:function(e,t,n){n.d(t,{Z:function(){return l}});var a=n(9028);class l{constructor(e,t={}){this.baseUrl=e,this.options=t}query(e){const t=(0,a.nZ)(e);return a.hi.get(this.baseUrl+t).then((e=>this.transformToEntity(e.data)))}find(e){return a.hi.get(`${this.baseUrl}/${e}`).then((e=>this.transformToEntity(e.data)))}save(e){return a.hi.post(this.baseUrl,e).then((e=>this.transformToEntity(e.data)))}update(e){return a.hi.put(`${this.baseUrl}/${e.id}`,e).then((e=>this.transformToEntity(e.data)))}destroy(e){return a.hi["delete"](`${this.baseUrl}/${e.id}`)}transformToEntity(e){return this.options.entityClass&&Array.isArray(e)?e.map((e=>new this.options.entityClass(e))):e}}},3600:function(e,t,n){var a=n(6381),l=n(9028);const o=new a.Z("/api/comments");o.resolve=e=>l.hi.post(`/api/comments/${e}/resolved`).then((e=>e.data)),o.unresolve=e=>l.hi.post(`/api/comments/${e}/unresolved`).then((e=>e.data)),t["Z"]=o},7411:function(e,t,n){n.d(t,{Z:function(){return E}});var a=n(6252),l=n(2262),o=n(3577),r=n(3907),s=n(1234),i=(n(6699),n(9669)),u=n.n(i),m=n(865),c=n(6350),d=n(4643),p=n(5699);const v={colspan:"6",class:"bg-white border-0 pl-0 pb-1 pt-3"},_={class:"text-lg"},w=(0,a.Uk)("   "),b=(0,a._)("thead",null,[(0,a._)("tr",{class:"text-sm"},[(0,a._)("th",null,"Name"),(0,a._)("th",null,"Credentials"),(0,a._)("th",null,"Expertise"),(0,a._)("th",null,"Institution"),(0,a._)("th",null,"Publications")])],-1),g={class:"text-sm"},y={key:0},h=["href"],f=["onClick"];var k={__name:"ReviewMembership",props:{members:{required:!0,type:Array}},setup(e){const t=e,n=(0,a.Fl)((()=>(0,m.Fs)("ep-applications-approve"))),r=(0,l.iH)(["name","credentials","expertise","institution"]);(0,m.Fs)("ep-applications-manage")&&r.value.push("coi_completed");const s=(0,l.iH)([]),i=(0,a.Fl)((()=>x.value.filter((e=>e.roles.includes("chair"))))),k=(0,a.Fl)((()=>x.value.filter((e=>e.roles.includes("expert"))))),U=(0,a.Fl)((()=>[{title:"Leadership",members:i.value},{title:"Coordination",members:x.value.filter((e=>e.roles.includes("coordinator")))},{title:"Biocuration",members:x.value.filter((e=>e.roles.includes("biocurator")))},{title:"Expertise",members:k.value}])),C=(e=1e3)=>new Promise((t=>setTimeout(t,e))),S=async e=>{const t="https://eutils.ncbi.nlm.nih.gov/entrez/eutils",n=`${t}/esearch.fcgi?db=pubmed&term=${e.last_name},+${e.first_name}[author]&retmode=json&retmax=0`;return u().get(n).then((t=>{e.pubCount=t.data.esearchresult.count})).catch((async t=>{429==t.response.status&&(await C(),S(e))}))};(0,a.YP)((()=>t.members),(async e=>{if(e){s.value=[...e],s.value.sort(((e,t)=>e.roles.includes("chair")&&!t.roles.includes("chair")?-1:!e.roles.includes("chair")&&t.roles.includes("chair")?1:e.roles.includes("expert")&&!t.roles.includes("expert")?-1:!e.roles.includes("expert")&&t.roles.includes("expert")?1:0));for(let e in s.value.filter((e=>e.roles.includes("chair")||e.roles.includes("expert"))))n.value&&(S(s.value[e]),await C(500))}}),{immediate:!0});const x=(0,a.Fl)((()=>t.members.map((e=>{const t={id:e.id,first_name:e.person.first_name,last_name:e.person.last_name,name:e.person.name,institution:e.person.institution?e.person.institution.name:null,legacy_credentials:e.person.legacy_credentials,legacy_expertise:e.legacy_expertise,roles:e.roles.map((e=>e.name)).join(", "),person:e.person};return(0,m.Fs)("ep-applications-manage")&&(t.coi_completed=(0,p.p6)(e.coi_last_completed)),t}))));return(e,t)=>{const n=(0,a.up)("badge"),r=(0,a.up)("popper");return(0,a.wg)(),(0,a.iD)("div",null,[(0,a._)("table",null,[((0,a.wg)(!0),(0,a.iD)(a.HY,null,(0,a.Ko)((0,l.SU)(U),(e=>((0,a.wg)(),(0,a.iD)(a.HY,{key:e.title},[(0,a._)("thead",null,[(0,a._)("tr",null,[(0,a._)("th",v,[(0,a._)("span",_,(0,o.zw)(e.title),1),w,(0,a.Wm)(n,{size:"xxs"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)(e.members.length),1)])),_:2},1024)])])]),e.members.length>0?((0,a.wg)(),(0,a.iD)(a.HY,{key:0},[b,(0,a._)("tbody",g,[((0,a.wg)(!0),(0,a.iD)(a.HY,null,(0,a.Ko)(e.members,(e=>((0,a.wg)(),(0,a.iD)("tr",{key:e.id},[(0,a._)("td",null,(0,o.zw)(e.person.name),1),(0,a._)("td",null,[(0,a.Wm)(c.Z,{person:e.person},null,8,["person"])]),(0,a._)("td",null,[(0,a.Wm)(d.Z,{person:e.person,legacyExpertise:e.legacy_expertise},null,8,["person","legacyExpertise"])]),(0,a._)("td",null,(0,o.zw)(e.institution),1),(0,a._)("td",null,[e.pubCount?((0,a.wg)(),(0,a.iD)("div",y,[e.pubCount>0?((0,a.wg)(),(0,a.j4)(r,{key:0,content:"Go to PubMed results.",hover:"",arrow:"",placement:"left"},{default:(0,a.w5)((()=>[(0,a._)("a",{href:`https://pubmed.ncbi.nlm.nih.gov/?term=${e.last_name},+${e.first_name}%5BAuthor%5D`,target:"pubmed"},[(0,a.Wm)(n,{size:"xxs"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)(e.pubCount),1)])),_:2},1024)],8,h)])),_:2},1024)):((0,a.wg)(),(0,a.j4)(n,{key:1,size:"xxs"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)(e.pubCount),1)])),_:2},1024))])):((0,a.wg)(),(0,a.iD)("button",{key:1,class:"btn btn-xs",onClick:t=>S(e)},"Get",8,f))])])))),128))])],64)):(0,a.kq)("",!0)],64)))),128))])])}}};const U=k;var C=U;const S={class:"space-y-4"},x={key:0,class:"mt-6"},W=(0,a._)("h4",null,"Expertise of VCEP members",-1),j=(0,a._)("h3",null,"Genes",-1),D={class:"mb-6"},q={key:0},z=(0,a._)("h3",null,"Description of scope",-1),I={class:"flex-none"},M={key:0,class:"mt-1"},F=(0,a._)("em",null,"Details:",-1);var R={__name:"DefinitionReview",setup(e){const t=(0,r.oR)(),n=(0,a.Fl)((()=>t.getters["groups/currentItemOrNew"])),i=(0,a.Fl)((()=>n.value.expert_panel)),u=(0,a.Fl)((()=>n.value?n.value.members:[])),m=(0,a.Fl)((()=>({type:n.value.type.name?n.value.type.name.toUpperCase():"",long_base_name:i.value.long_base_name,short_base_name:i.value.short_base_name})));return(e,t)=>{const r=(0,a.up)("object-dictionary"),c=(0,a.up)("dictionary-row"),d=(0,a.up)("markdown-block"),v=(0,a.up)("simple-table");return(0,a.wg)(),(0,a.iD)("div",S,[(0,a.Wm)(s.Z,{title:"Basic Information",name:"basic-info"},{default:(0,a.w5)((()=>[(0,a.Wm)(r,{obj:(0,l.SU)(m),"label-class":"w-40 font-bold"},null,8,["obj"]),(0,a.Wm)(c,{label:"CDWG","label-class":"w-40 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(n).parent?(0,l.SU)(n).parent.name:"--"),1)])),_:1})])),_:1}),(0,a.Wm)(s.Z,{title:"Membership",name:"membership"},{default:(0,a.w5)((()=>[(0,a.Wm)(C,{members:(0,l.SU)(u)},null,8,["members"]),(0,l.SU)(n).isVcep()?((0,a.wg)(),(0,a.iD)("div",x,[W,(0,a._)("blockquote",null,[(0,a.Wm)(d,{markdown:(0,l.SU)(i).membership_description},null,8,["markdown"])])])):(0,a.kq)("",!0)])),_:1}),(0,a.Wm)(s.Z,{title:"Scope",name:"scope"},{default:(0,a.w5)((()=>[j,(0,a._)("div",D,[(0,l.SU)(n).isGcep()?((0,a.wg)(),(0,a.iD)("p",q,(0,o.zw)((0,l.SU)(i).genes.map((e=>e.gene_symbol)).join(", ")),1)):(0,a.kq)("",!0),(0,l.SU)(n).isVcep()?((0,a.wg)(),(0,a.j4)(v,{key:1,data:(0,l.SU)(i).genes.map((e=>({id:e.id,gene:e.gene_symbol,disease:e.disease_name}))),"key-by":"id","hide-columns":["id"]},null,8,["data"])):(0,a.kq)("",!0)]),z,(0,a._)("blockquote",null,[(0,a.Wm)(d,{markdown:(0,l.SU)(i).scope_description},null,8,["markdown"])])])),_:1}),(0,l.SU)(n).isGcep()?((0,a.wg)(),(0,a.j4)(s.Z,{key:0,title:"Plans",name:"plans"},{default:(0,a.w5)((()=>[(0,a.Wm)(c,{label:"Selected protocol","label-class":"w-48 font-bold"},{default:(0,a.w5)((()=>[(0,a._)("div",I,[(0,a.Uk)((0,o.zw)((0,l.SU)(i).curation_review_protocol?e.titleCase((0,l.SU)(i).curation_review_protocol.full_name):null)+" ",1),100==(0,l.SU)(i).curation_review_protocol_id?((0,a.wg)(),(0,a.iD)("p",M,[F,(0,a.Uk)(" "+(0,o.zw)((0,l.SU)(i).curation_review_protocol_other),1)])):(0,a.kq)("",!0)])])),_:1})])),_:1})):(0,a.kq)("",!0),(0,l.SU)(n).isGcep()?((0,a.wg)(),(0,a.j4)(s.Z,{key:1,title:"Attestations",name:"attestations"},{default:(0,a.w5)((()=>[(0,a.Wm)(c,{label:"GCEP Attestation Signed","label-class":"w-52 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(p.p6)((0,l.SU)(i).gcep_attestation_date)),1)])),_:1}),(0,a.Wm)(c,{label:"GCI Training Date","label-class":"w-52 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(p.p6)((0,l.SU)(i).gci_training_date)),1)])),_:1}),(0,a.Wm)(c,{label:"NHGRI Attestation Signed","label-class":"w-52 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(p.p6)((0,l.SU)(i).nhgri_attestation_date)),1)])),_:1})])),_:1})):(0,a.kq)("",!0),(0,l.SU)(n).isVcep()?((0,a.wg)(),(0,a.j4)(s.Z,{key:2,title:"Attestations",name:"attestations"},{default:(0,a.w5)((()=>[(0,a.Wm)(c,{label:"Reanalysis and Discrepancy Resolution Attestation Signed","label-class":"w-52 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(p.p6)((0,l.SU)(i).reanalysis_attestation_date)),1)])),_:1}),(0,a.Wm)(c,{label:"NHGRI Attestation Signed","label-class":"w-60 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(p.p6)((0,l.SU)(i).nhgri_attestation_date)),1)])),_:1})])),_:1})):(0,a.kq)("",!0)])}}};const Z=R;var E=Z},1234:function(e,t,n){n.d(t,{Z:function(){return ee}});var a=n(6252),l=n(3577),o=n(2262),r=n(9963),s=n(3907),i=n(9028);class u{constructor(e,t){this.fields=e,this.repo=t,this.currentItem=(0,o.iH)({}),this.originalItem=(0,o.iH)({}),this.errors=(0,o.iH)({})}async find(e){return await this.repo.find(e).then((e=>(this.currentItem.value=e,this.originalItem.value=e,e)))}async save(e){this.clearErrors();try{const t=await this.repo.save(e);return this.clearCurrentItem(),t}catch(t){throw(0,i.z1)(t)&&(this.errors.value=t.response.data.errors),t}}async update(e){this.clearErrors();try{return this.currentItem.value=this.repo.update(e),this.currentItem.value}catch(t){throw(0,i.z1)(t)&&(this.errors.value=t.response.data.errors),t}}async destroy(e){this.repo.destroy(e)}cancel(){this.clearErrors(),this.currentItem.value.id||this.clearCurrentItem()}clearCurrentItem(){this.currentItem.value={}}clearErrors(){this.errors.value={}}}var m=u,c=n(3600),d=(n(1703),n(6381));class p extends d.Z{constructor(e,t={}){super(e,t),this.cachedList=null}query(e){if(!this.cachedList){const t=super.query(e);this.cachedList=t}return this.cachedList}find(e){throw Error("CommentTypeRepository.find not implemented")}save(e){throw Error("CommentTypeRepository.save not implemented")}update(e){throw Error("CommentTypeRepository.update not implemented")}destroy(e){throw Error("CommentTypeRepository.destroy not implemented")}}const v=new p("/api/comment-types");var _=v;const w=(0,o.iH)([]),b=async()=>await _.query().then((e=>(w.value=e.map((e=>({label:e.name,value:e.id}))),w.value))),g=(0,o.iH)([{name:"comment_type_id",label:"Type",type:"select",options:w,required:!0},{name:"content",label:"Comment",type:"large-text",required:!0}]);var y=()=>(b(),new m(g,c.Z)),h={__name:"ReviewCommentForm",props:{commentManager:{type:Object,required:!0},section:{type:[String,null],default:null},subjectType:{type:String,default:"App\\Modules\\Group\\Models\\Group"},subjectId:{type:Number,required:!1},comment:{type:Object},onlyInternal:{type:Boolean,default:!1}},emits:["saved","canceled"],setup(e,{emit:t}){const n=e,l=(0,a.f3)("group");(0,a.bv)((()=>{n.comment&&(r.currentItem.value=n.comment)}));const r=y(),s=(0,a.Fl)((()=>{const e=[...r.fields.value];if(n.onlyInternal){const t=e.findIndex((e=>"comment_type_id"==e.name));e.splice(t,1)}return e})),i=(0,a.Fl)((()=>r.errors.value)),u=(0,a.Fl)({get(){return r.currentItem.value},set(e){r.currentItem.value=e}}),m=()=>{r.clearCurrentItem(),r.clearErrors(),t("canceled")},c=()=>{u.value.subject_type=n.subjectType,u.value.subject_id=n.subjectId||l.value.id,u.value.metadata={section:n.section,root_subject_type:"App\\Modules\\Group\\Models\\Group",root_subject_id:l.value.id},n.onlyInternal&&(u.value.comment_type_id=1),r.save(u.value).then((e=>{n.commentManager.addComment(e),t("saved",e)}))},d=()=>{n.onlyInternal&&(u.value.comment_type_id=1),r.update(u.value).then((e=>{n.commentManager.updateComment(e),t("saved",e)}))},p=()=>{u.value.id?d():c()};return(e,t)=>{const n=(0,a.up)("data-form"),l=(0,a.up)("button-row");return(0,a.wg)(),(0,a.iD)("div",null,[(0,a.Wm)(n,{fields:(0,o.SU)(s),modelValue:(0,o.SU)(u),"onUpdate:modelValue":t[0]||(t[0]=e=>(0,o.dq)(u)?u.value=e:null),errors:(0,o.SU)(i)},null,8,["fields","modelValue","errors"]),(0,a.Wm)(l,{size:"xs","submit-text":"Save",onSubmitted:p,onCanceled:m})])}}};const f=h;var k=f,U=n(2839),C=n(417),S=n(865);const x={class:"my-2"},W={class:"comment-container"},j={key:0,class:"relative"},D={class:"flex justify-between items-start mb-1 rounded"},q={class:"flex space-x-2 items-end"},z={class:"block"},I={class:"flex space-x-2"},M=(0,a.Uk)("Edit"),F=(0,a.Uk)("Delete"),R=(0,a.Uk)("Reply"),Z=(0,a.Uk)(" Continue with delete? "),E={class:"replies ml-1"},H={key:0},T={class:"border-l-2 mt-2 px-2 py-1 bg-gray-100 bg-opacity-50"},A=(0,a._)("strong",null,"Your Reply",-1);var G={__name:"ReviewComment",props:{comment:{type:Object,required:!0},commentManager:{type:Object,required:!0}},emits:["created","updated","resolved","unresolved","deleted"],setup(e,{emit:t}){const n=e,i=(0,s.oR)(),u=y(),m=(0,o.iH)((0,C.Z)("App\\Models\\Comment",n.comment.id)),d=(0,o.iH)(!1),p=(0,o.iH)(!1),v=e=>{if(!e.type)return"gray";switch(e.type.name){case"required revision":return"yellow";case"suggestion":return"blue";case"internal comment":return"gray";default:break}},_=()=>{p.value=!0},w=()=>{u.destroy(n.comment).then((()=>{p.value=!1,n.commentManager.removeComment(n.comment),t("deleted",n.comment)}))},b=async()=>{if(n.comment.is_resolved){const e=await c.Z.unresolve(n.comment.id);return n.commentManager.updateComment(e),t("updated"),void t("resolved")}const e=await c.Z.resolve(n.comment.id);n.commentManager.updateComment(e),t("updated"),t("unresolved")},g=(0,o.iH)(!1),h=async()=>{g.value||f(),g.value=!g.value},f=()=>{m.value.getComments().then((e=>(g.value=!0,e)))},G=(0,o.iH)(!1),$=()=>G.value=!0,Y=()=>{const e={...n.comment};e.comments_count+=1,n.commentManager.updateComment(e),g.value=!0,G.value=!1},B=()=>{const e={...n.comment};e.comments_count-=1,n.commentManager.updateComment(e)};(0,a.bv)((()=>{m.value.getComments()}));const P=(0,a.Fl)((()=>(0,S.Fs)("comments-manage")||i.getters.currentUser.person.id==n.comment.creator_id));return(t,n)=>{const s=(0,a.up)("badge"),i=(0,a.up)("icon-checkmark"),u=(0,a.up)("popper"),c=(0,a.up)("dropdown-menu"),y=(0,a.up)("markdown-block"),f=(0,a.up)("icon-reply"),C=(0,a.up)("button-row"),S=(0,a.up)("static-alert"),V=(0,a.up)("ReviewComment",!0);return(0,a.wg)(),(0,a.iD)("div",x,[(0,a._)("div",W,[d.value?((0,a.wg)(),(0,a.j4)(k,{key:1,onCanceled:n[2]||(n[2]=e=>d.value=!1),onSaved:n[3]||(n[3]=e=>d.value=!1),comment:e.comment,commentManager:e.commentManager},null,8,["comment","commentManager"])):((0,a.wg)(),(0,a.iD)("div",j,[(0,a._)("div",D,[(0,a._)("div",q,[(0,a._)("strong",z,(0,l.zw)(e.comment.creator&&e.comment.creator.name),1),(0,a.Wm)(s,{class:"block",color:v(e.comment),size:"xxs"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,l.zw)(e.comment.type&&t.titleCase(e.comment.type.name)),1)])),_:1},8,["color"]),e.comment.is_resolved?((0,a.wg)(),(0,a.j4)(u,{key:0,hover:"",arrow:"",content:"Resolved"},{default:(0,a.w5)((()=>[(0,a.Wm)(i,{class:"text-green-500",title:"Resolved"})])),_:1})):(0,a.kq)("",!0)]),(0,a._)("div",I,[(0,o.SU)(P)?((0,a.wg)(),(0,a.j4)(c,{key:0,hideCheveron:""},{default:(0,a.w5)((()=>[(0,a.Wm)(U["default"],{onClick:n[0]||(n[0]=e=>d.value=!0)},{default:(0,a.w5)((()=>[M])),_:1}),(0,a.Wm)(U["default"],{onClick:b},{default:(0,a.w5)((()=>[(0,a.Uk)((0,l.zw)(e.comment.is_resolved?"Mark unresolved":"Resolve"),1)])),_:1}),(0,a.Wm)(U["default"],{onClick:_},{default:(0,a.w5)((()=>[F])),_:1})])),_:1})):(0,a.kq)("",!0)])]),(0,a.Wm)(y,{markdown:e.comment.content,class:"text-sm"},null,8,["markdown"]),(0,a._)("button",{class:"link",onClick:$},[(0,a.Wm)(f,{class:"inline-block"}),R]),(0,a.wy)((0,a.Wm)(S,{variant:"danger",class:""},{default:(0,a.w5)((()=>[Z,(0,a.Wm)(C,{size:"xs","submit-text":"Yes, delete",onSubmitted:w,onCanceled:n[1]||(n[1]=e=>p.value=!1),submitVariant:"red"})])),_:1},512),[[r.F8,p.value]])]))]),(0,a._)("div",E,[g.value?((0,a.wg)(),(0,a.iD)("ul",H,[((0,a.wg)(!0),(0,a.iD)(a.HY,null,(0,a.Ko)(m.value.comments,(e=>((0,a.wg)(),(0,a.iD)("li",{key:e.id,class:"border-l-2 mt-2 px-2 py-1 bg-gray-100 bg-opacity-50"},[(0,a.Wm)(V,{comment:e,commentManager:m.value,onDeleted:B},null,8,["comment","commentManager"])])))),128))])):(0,a.kq)("",!0),e.comment.comments_count>0?((0,a.wg)(),(0,a.iD)("button",{key:1,class:"link text-sm",onClick:h},(0,l.zw)(g.value?"Hide":"Show")+" "+(0,l.zw)(e.comment.comments_count)+" "+(0,l.zw)(e.comment.comments_count>1?"replies":"reply"),1)):(0,a.kq)("",!0),(0,a.wy)((0,a._)("div",T,[A,(0,a.Wm)(k,{subjectType:"App\\Models\\Comment",subjectId:e.comment.id,onlyInternal:!0,commentManager:m.value,onSaved:Y,onCanceled:n[4]||(n[4]=e=>G.value=!1)},null,8,["subjectId","commentManager"])],512),[[r.F8,G.value]])])])}}};const $=G;var Y=$;const B={class:"lg:flex lg:space-x-4 screen-block"},P={class:"flex justify-between items-start space-x-4"},V={key:0,class:"flex justify-between items-center lg:w-2/5 px-2 py-1 pb-0 bg-gray-100 rounded-lg"},N=(0,a.Uk)(" Comments "),O={class:"lg:w-2/5 p-2 bg-gray-100 rounded-lg mb-2"},L={class:"flex justify-between items-center"},K=(0,a.Uk)(" Comments "),J={class:"bg-white mt-2 p-2"};var Q={__name:"ReviewSection",props:{title:{type:String||null,default:null},name:{type:String||null,default:null}},setup(e){const t=e,n=(0,a.f3)("commentManager"),s=(0,o.iH)(!1),i=(0,o.iH)(!0),u=(0,a.Fl)((()=>n?n.value.comments.filter((e=>!!e.metadata&&e.metadata.section==t.name)):[])),m=(0,a.Fl)((()=>n?u.value.find((e=>"required revision"==e.type.name))?"yellow":u.value.find((e=>"suggestion"==e.type.name))?"blue":"gray":null));return(t,c)=>{const d=(0,a.up)("icon-cheveron-right"),p=(0,a.up)("badge"),v=(0,a.up)("icon-cheveron-down");return(0,a.wg)(),(0,a.iD)("section",B,[(0,a._)("div",{class:(0,l.C_)(["overflow-x-auto flex-grow",{"lg:w-3/5":i.value}])},[(0,a._)("header",P,[(0,a._)("h2",{class:(0,l.C_)(["flex-grow",{"lg:w-3/5":!i.value}])},(0,l.zw)(e.title),3),(0,o.SU)(n)?(0,a.wy)(((0,a.wg)(),(0,a.iD)("div",V,[(0,a._)("h3",null,[(0,a.Wm)(d,{class:"inline cursor-pointer",onClick:c[0]||(c[0]=e=>i.value=!0)}),N,(0,a.Wm)(p,{color:(0,o.SU)(m)},{default:(0,a.w5)((()=>[(0,a.Uk)((0,l.zw)((0,o.SU)(u).length),1)])),_:1},8,["color"])])],512)),[[r.F8,!i.value]]):(0,a.kq)("",!0)]),(0,a._)("div",null,[(0,a._)("div",null,[(0,a.WI)(t.$slots,"default")])])],2),(0,a.wy)((0,a._)("div",O,[(0,a._)("div",L,[(0,a._)("h3",null,[(0,a.Wm)(v,{class:"inline cursor-pointer",onClick:c[1]||(c[1]=e=>i.value=!1)}),K])]),(0,a._)("ul",null,[((0,a.wg)(!0),(0,a.iD)(a.HY,null,(0,a.Ko)((0,o.SU)(u),(e=>((0,a.wg)(),(0,a.iD)("li",{key:e.id,class:"bg-white p-2"},[(0,a.Wm)(Y,{comment:e,commentManager:(0,o.SU)(n)},null,8,["comment","commentManager"])])))),128))]),(0,a._)("div",J,[s.value?((0,a.wg)(),(0,a.j4)(k,{key:0,section:e.name,commentManager:(0,o.SU)(n),onSaved:c[2]||(c[2]=e=>s.value=!1),onCanceled:c[3]||(c[3]=e=>s.value=!1)},null,8,["section","commentManager"])):((0,a.wg)(),(0,a.iD)("button",{key:1,class:"btn btn-xs block",onClick:c[4]||(c[4]=e=>s.value=!0)},"Add comment"))])],512),[[r.F8,i.value&&(0,o.SU)(n)]])])}}};const X=Q;var ee=X},3496:function(e,t,n){n.d(t,{Z:function(){return _}});var a=n(6252),l=n(2262),o=n(3577),r=n(9955),s=n(3907),i=n(1234);const u={class:"application-review p-2 bg-gray-100"},m={class:"w-full"},c={key:0,class:"mt-1"},d=(0,a._)("em",null,"Details:",-1);var p={__name:"SustainedCurationReview",setup(e){const t=(0,s.oR)(),n=(0,a.Fl)((()=>t.getters["groups/currentItemOrNew"])),p=(0,a.Fl)((()=>n.value.expert_panel));return(0,a.YP)((()=>n.value),((e,a)=>{!e.id||a&&e.id==a.id||t.dispatch("groups/getEvidenceSummaries",n.value)})),(e,t)=>{const s=(0,a.up)("dictionary-row"),v=(0,a.up)("markdown-block");return(0,a.wg)(),(0,a.iD)("div",u,[(0,l.SU)(p).has_approved_pilot?((0,a.wg)(),(0,a.j4)(i.Z,{key:0,title:"Plans for Ongoing Review and Discrepancy Resolution",name:"discrepency-review"},{default:(0,a.w5)((()=>[(0,a.Wm)(s,{label:"Selected protocol",labelWidthClass:"w-48 font-bold"},{default:(0,a.w5)((()=>[(0,a._)("div",m,[(0,a.Uk)((0,o.zw)((0,l.SU)(p).curation_review_protocol?e.titleCase((0,l.SU)(p).curation_review_protocol.full_name):null)+" ",1),100==(0,l.SU)(p).curation_review_protocol_id?((0,a.wg)(),(0,a.iD)("p",c,[d,(0,a.Uk)(" "+(0,o.zw)((0,l.SU)(p).curation_review_protocol_other),1)])):(0,a.kq)("",!0)])])),_:1}),(0,a.Wm)(s,{label:"Notes",labelWidthClass:"w-48 font-bold"},{default:(0,a.w5)((()=>[(0,a.Wm)(v,{markdown:(0,l.SU)(p).curation_review_process_notes},null,8,["markdown"])])),_:1})])),_:1})):(0,a.kq)("",!0),(0,l.SU)(p).has_approved_pilot?((0,a.wg)(),(0,a.j4)(i.Z,{key:1,title:"Evidence Summaries",name:"evidence-summaries"},{default:(0,a.w5)((()=>[(0,a.Wm)(r.Z,{readonly:!0})])),_:1})):(0,a.kq)("",!0),(0,l.SU)(p).has_approved_pilot?((0,a.wg)(),(0,a.j4)(i.Z,{key:2,title:"Core Approval Member, Trained Biocurator, and Biocurator Trainer Designation",name:"member-designation"},{default:(0,a.w5)((()=>[(0,a.Wm)(s,{label:"Core Approval Members",labelWidthClass:"w-48 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(n).coreApprovalMembers.map((e=>e.person.name)).join(", ")),1)])),_:1}),(0,a.Wm)(s,{label:"Biocurator Trainers",labelWidthClass:"w-48 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(n).biocuratorTrainers.map((e=>e.person.name)).join(", ")),1)])),_:1}),(0,a.Wm)(s,{label:"Trained Biocurators",labelWidthClass:"w-48 font-bold"},{default:(0,a.w5)((()=>[(0,a.Uk)((0,o.zw)((0,l.SU)(n).trainedBiocurators.map((e=>e.person.name)).join(", ")),1)])),_:1})])),_:1})):(0,a.kq)("",!0)])}}};const v=p;var _=v}}]);
//# sourceMappingURL=105.b3a2fae9.js.map