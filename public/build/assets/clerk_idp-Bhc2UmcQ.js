import{useAuth as i}from"./index-DnSzvkW7.js";import"./app-CBM2WbmF.js";function s(){const{isLoaded:u,isSignedIn:t,getToken:e,signOut:n}=i();return{driver:"clerk",isLoaded:u,isSignedIn:t,getToken:async()=>e.value?e.value():null,signOut:async()=>n.value?n.value():void 0}}export{s as useClerkIdp};
//# sourceMappingURL=clerk_idp-Bhc2UmcQ.js.map
