(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["reset-password"],{"033d":function(e,r,t){"use strict";r["a"]=function(e){return e.response&&422==e.response.status&&e.response.data.errors}},"0813":function(e,r,t){"use strict";t.r(r);var o=t("7a23"),s={key:0,class:"p-2 rounded border border-green-300 bg-green-100 text-green-700"},n={key:1};function a(e,r,t,a,i,c){var l=Object(o["resolveComponent"])("input-row"),u=Object(o["resolveComponent"])("button-row"),d=Object(o["resolveComponent"])("card");return Object(o["openBlock"])(),Object(o["createBlock"])(d,{title:"Reset Your Password",class:"w-2/3 mx-auto"},{default:Object(o["withCtx"])((function(){return[i.successMessage?(Object(o["openBlock"])(),Object(o["createBlock"])("div",s,Object(o["toDisplayString"])(i.successMessage),1)):(Object(o["openBlock"])(),Object(o["createBlock"])("div",n,[Object(o["createVNode"])(l,{modelValue:i.email,"onUpdate:modelValue":r[1]||(r[1]=function(e){return i.email=e}),type:"text",label:"Email",errors:i.errors.email},null,8,["modelValue","errors"]),c.hasToken?(Object(o["openBlock"])(),Object(o["createBlock"])(l,{key:0,modelValue:i.password,"onUpdate:modelValue":r[2]||(r[2]=function(e){return i.password=e}),type:"password",label:"New password",errors:i.errors.password},null,8,["modelValue","errors"])):Object(o["createCommentVNode"])("",!0),c.hasToken?(Object(o["openBlock"])(),Object(o["createBlock"])(l,{key:1,modelValue:i.password_confirmation,"onUpdate:modelValue":r[3]||(r[3]=function(e){return i.password_confirmation=e}),type:"password",label:"Confirm password",errors:i.errors.password_confirmation},null,8,["modelValue","errors"])):Object(o["createCommentVNode"])("",!0),Object(o["createVNode"])(u,{"show-cancel":!1,"submit-text":"Send Password Reset Link",onSubmitClicked:c.submitReset},null,8,["onSubmitClicked"])]))]})),_:1})}t("ac1f"),t("5319");var i=t("f96b"),c=t("033d"),l={props:{},data:function(){return{email:null,errors:{},password:null,password_confirmation:null,successMessage:null}},computed:{hasToken:function(){return Boolean(this.$route.query.token)}},methods:{getResetLink:function(){var e=this;i["a"].post("/api/send-reset-password-link",{email:this.email}).then((function(r){e.successMessage=r.data.status})).catch((function(r){Object(c["a"])(r)&&(e.errors=r.response.data.errors)}))},submitNewPassword:function(){var e=this,r={token:this.$route.query.token,email:this.email,password:this.password,password_confirmation:this.password_confirmation};i["a"].post("/api/reset-password",r).then((function(){e.$store.dispatch("login",{email:e.email,password:e.password}).then((function(){e.$store.dispatch("getCurrentUser"),e.$router.replace("/")}))})).catch((function(r){Object(c["a"])(r)&&(e.errors=r.response.data.errors)}))},submitReset:function(){this.hasToken?this.submitNewPassword():this.getResetLink()}}};l.render=a;r["default"]=l},"0cb2":function(e,r,t){var o=t("7b0b"),s=Math.floor,n="".replace,a=/\$([$&'`]|\d\d?|<[^>]*>)/g,i=/\$([$&'`]|\d\d?)/g;e.exports=function(e,r,t,c,l,u){var d=t+e.length,p=c.length,f=i;return void 0!==l&&(l=o(l),f=a),n.call(u,f,(function(o,n){var a;switch(n.charAt(0)){case"$":return"$";case"&":return e;case"`":return r.slice(0,t);case"'":return r.slice(d);case"<":a=l[n.slice(1,-1)];break;default:var i=+n;if(0===i)return o;if(i>p){var u=s(i/10);return 0===u?o:u<=p?void 0===c[u-1]?n.charAt(1):c[u-1]+n.charAt(1):o}a=c[i-1]}return void 0===a?"":a}))}},5319:function(e,r,t){"use strict";var o=t("d784"),s=t("825a"),n=t("50c4"),a=t("a691"),i=t("1d80"),c=t("8aa5"),l=t("0cb2"),u=t("14c3"),d=Math.max,p=Math.min,f=function(e){return void 0===e?e:String(e)};o("replace",2,(function(e,r,t,o){var b=o.REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE,h=o.REPLACE_KEEPS_$0,m=b?"$":"$0";return[function(t,o){var s=i(this),n=void 0==t?void 0:t[e];return void 0!==n?n.call(t,s,o):r.call(String(s),t,o)},function(e,o){if(!b&&h||"string"===typeof o&&-1===o.indexOf(m)){var i=t(r,e,this,o);if(i.done)return i.value}var w=s(e),v=String(this),k="function"===typeof o;k||(o=String(o));var g=w.global;if(g){var O=w.unicode;w.lastIndex=0}var j=[];while(1){var S=u(w,v);if(null===S)break;if(j.push(S),!g)break;var $=String(S[0]);""===$&&(w.lastIndex=c(v,n(w.lastIndex),O))}for(var y="",C=0,E=0;E<j.length;E++){S=j[E];for(var V=String(S[0]),_=d(p(a(S.index),v.length),0),x=[],B=1;B<S.length;B++)x.push(f(S[B]));var R=S.groups;if(k){var N=[V].concat(x,_,v);void 0!==R&&N.push(R);var P=String(o.apply(void 0,N))}else P=l(V,v,_,x,R,o);_>=C&&(y+=v.slice(C,_)+P,C=_+V.length)}return y+v.slice(C)}]}))}}]);
//# sourceMappingURL=reset-password.fbb92096.js.map