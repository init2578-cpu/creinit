import{T as I,a as c,f as n,u as s,Z as B,w as T,b as e,i as m,o as l,F as M,m as F,t as a,n as w,y as k,l as u,x as O,k as f,v as q,O as E,N as P,p as g}from"./vendor-DmkAW-zK.js";import{_ as G}from"./AuthenticatedLayout-Cv_A4veL.js";import{j as L,ag as S,ah as _,k as W,A as H,ai as Y,n as Z,d as J,S as N,aj as K}from"./heroicons-CRqW7Xm0.js";import"./axios-42ANG6Sg.js";import"./lodash-sVzdDwke.js";import"./chartjs-BznfGPhk.js";/* empty css                                                                     */import"./_plugin-vue_export-helper-DlAUqK2U.js";const X={class:"max-w-7xl mx-auto py-8 px-4"},ee={class:"mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4"},te={class:"bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden"},se={class:"w-full text-left border-collapse"},oe={class:"divide-y divide-gray-50"},re={class:"px-8 py-5"},ie={class:"flex items-center gap-4"},ne={class:"h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center"},le={class:"font-black text-gray-900 leading-tight"},ae=["onClick"],de={class:"text-[10px] font-mono font-bold uppercase underline decoration-dashed"},ce={class:"px-8 py-5"},ue={class:"px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black tracking-tight border border-gray-200"},pe={class:"px-8 py-5 text-center"},ge={class:"px-8 py-5 text-center"},xe={class:"px-8 py-5 text-xs text-gray-400 font-bold"},be={class:"px-8 py-5 text-right"},me={class:"flex justify-end gap-2 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"},fe=["onClick"],he=["onClick"],ye=["onClick"],ve={key:0},we={key:0,class:"fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"},ke={class:"bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl relative"},_e={class:"text-2xl font-black text-gray-900 tracking-tight mb-2"},Ce={class:"relative"},$e={key:0,class:"text-xs text-red-500 mt-1.5 font-bold ml-1"},je={class:"relative"},Me={class:"grid grid-cols-2 gap-4"},qe={class:"flex gap-4 pt-4"},Ee=["disabled"],Se={key:1,class:"fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"},Ne={class:"bg-white w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl relative text-center"},Ae={key:0,class:"space-y-6"},Ve={class:"p-4 bg-slate-50 rounded-[2rem] inline-block relative"},ze=["src","alt"],Qe={class:"font-black text-gray-900 text-lg leading-tight"},Ue={class:"text-xs text-gray-400 font-mono mt-1 uppercase font-bold"},De={key:0,class:"inline-block mt-2 px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black border border-gray-200"},Re={class:"flex gap-4 pt-2"},We={__name:"AssetsIndex",props:{assets:Array},setup(C){const x=g(!1),p=g(!1),h=g(null),y=g(!1),d=g(null);function $(r){d.value=r,y.value=!0}function j(){y.value=!1,d.value=null}function A(){if(!d.value)return;const r=d.value,t=window.open("","_blank");t.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>QR Code - ${r.nom}</title>
                <style>
                    body {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 90vh;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        margin: 0;
                        color: #1e293b;
                    }
                    .card {
                        border: 2px solid #e2e8f0;
                        border-radius: 1.5rem;
                        padding: 2.5rem;
                        text-align: center;
                        max-width: 320px;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
                    }
                    .qr-container {
                        position: relative;
                        display: inline-block;
                    }
                    img.qr {
                        width: 220px;
                        height: 220px;
                        border: 4px solid #f1f5f9;
                        border-radius: 1rem;
                    }
                    .logo-overlay {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background-color: white;
                        padding: 4px;
                        border-radius: 8px;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                        border: 1px solid #e2e8f0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .logo-overlay img {
                        width: 44px;
                        height: 44px;
                        object-fit: contain;
                        border-radius: 4px;
                    }
                    h1 {
                        margin-top: 1.5rem;
                        margin-bottom: 0.5rem;
                        font-size: 1.25rem;
                        font-weight: 800;
                    }
                    p {
                        font-size: 0.875rem;
                        color: #64748b;
                        margin: 0.25rem 0;
                        font-weight: 500;
                    }
                    .serial {
                        display: inline-block;
                        background-color: #f1f5f9;
                        padding: 0.25rem 0.75rem;
                        border-radius: 0.5rem;
                        font-size: 0.75rem;
                        font-weight: 700;
                        margin-top: 0.5rem;
                    }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="card">
                    <div class="qr-container">
                        <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${r.uuid}" />
                        <div class="logo-overlay">
                            <img src="/images/logo-cre.png" alt="CRE Logo" />
                        </div>
                    </div>
                    <h1>${r.nom}</h1>
                    <p>UUID: ${r.uuid.substring(0,8).toUpperCase()}</p>
                    ${r.serie?`<div class="serial">S/N: ${r.serie}</div>`:""}
                </div>
            </body>
        </html>
    `),t.document.close()}const i=I({nom:"",serie:"",etat:"bon",status:"disponible"});function V(){p.value=!1,h.value=null,i.reset(),i.clearErrors(),x.value=!0}function z(r){p.value=!0,h.value=r,i.clearErrors(),i.nom=r.nom,i.serie=r.serie||"",i.etat=r.etat,i.status=r.status,x.value=!0}function b(){x.value=!1}function Q(){p.value?i.put(route("assets.update",h.value.id),{onSuccess:()=>b()}):i.post(route("assets.store"),{onSuccess:()=>b()})}function U(r){confirm("Êtes-vous sûr de vouloir supprimer ce matériel ? Cette action est irréversible.")&&P.delete(route("assets.destroy",r))}const D=r=>{switch(r){case"disponible":return"bg-green-50 text-green-600";case"preté":return"bg-blue-50 text-blue-600";case"maintenance":return"bg-amber-50 text-amber-600";default:return"bg-gray-50 text-gray-600"}},R=r=>{switch(r){case"bon":return"bg-emerald-50 text-emerald-700 border-emerald-100";case"endommagé":return"bg-orange-50 text-orange-700 border-orange-100";case"hors_service":return"bg-red-50 text-red-700 border-red-100";default:return"bg-gray-50 text-gray-700 border-gray-100"}};return(r,t)=>(l(),c(M,null,[n(s(B),{title:"Gestion du Matériel"}),n(G,null,{default:T(()=>[e("div",X,[e("header",ee,[t[5]||(t[5]=e("div",null,[e("h1",{class:"text-3xl font-black text-gray-900 tracking-tight"},"Gestion du Matériel"),e("p",{class:"text-gray-500 mt-1"},"Inventaire physique et suivi de l'état des équipements.")],-1)),e("button",{onClick:V,class:"flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all group active:scale-95"},[n(s(L),{class:"h-5 w-5 group-hover:rotate-90 transition-transform duration-300"}),t[4]||(t[4]=m(" Nouveau Matériel ",-1))])]),e("div",te,[e("table",se,[t[7]||(t[7]=e("thead",null,[e("tr",{class:"bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest"},[e("th",{class:"px-8 py-4"},"Matériel & ID"),e("th",{class:"px-8 py-4"},"Numéro de Série"),e("th",{class:"px-8 py-4 text-center"},"État Physique"),e("th",{class:"px-8 py-4 text-center"},"Disponibilité"),e("th",{class:"px-8 py-4"},"Ajouté le"),e("th",{class:"px-8 py-4"})])],-1)),e("tbody",oe,[(l(!0),c(M,null,F(C.assets,o=>(l(),c("tr",{key:o.id,class:"hover:bg-gray-50/50 transition-colors group"},[e("td",re,[e("div",ie,[e("div",ne,[n(s(S),{class:"h-6 w-6"})]),e("div",null,[e("p",le,a(o.nom),1),e("button",{onClick:v=>$(o),class:"flex items-center gap-1.5 mt-1 text-slate-400 hover:text-indigo-600 transition group/qr",title:"Afficher le QR Code"},[n(s(_),{class:"h-3.5 w-3.5"}),e("span",de,a(o.uuid.substring(0,8)),1)],8,ae)])])]),e("td",ce,[e("span",ue,a(o.serie||"N/A"),1)]),e("td",pe,[e("span",{class:w(["inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border",R(o.etat)])},[o.etat==="bon"?(l(),k(s(W),{key:0,class:"h-3.5 w-3.5"})):o.etat==="endommagé"?(l(),k(s(H),{key:1,class:"h-3.5 w-3.5"})):(l(),k(s(Y),{key:2,class:"h-3.5 w-3.5"})),m(" "+a(o.etat.replace("_"," ")),1)],2)]),e("td",ge,[e("span",{class:w(["inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider",D(o.status)])},[e("span",{class:w(["h-1.5 w-1.5 rounded-full",o.status==="disponible"?"bg-green-600":o.status==="preté"?"bg-blue-600":"bg-amber-600"])},null,2),m(" "+a(o.status),1)],2)]),e("td",xe,a(o.created_at),1),e("td",be,[e("div",me,[e("button",{onClick:v=>$(o),class:"p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50",title:"Voir le QR Code"},[n(s(_),{class:"h-5 w-5"})],8,fe),e("button",{onClick:v=>z(o),class:"p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50",title:"Modifier"},[n(s(Z),{class:"h-5 w-5"})],8,he),e("button",{onClick:v=>U(o.id),class:"p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm shadow-red-50",title:"Supprimer"},[n(s(J),{class:"h-5 w-5"})],8,ye)])])]))),128)),C.assets.length===0?(l(),c("tr",ve,[...t[6]||(t[6]=[e("td",{colspan:"6",class:"px-8 py-12 text-center text-gray-400 italic font-medium"}," Aucun matériel enregistré dans l'inventaire. ",-1)])])):u("",!0)])])])]),x.value?(l(),c("div",we,[e("div",ke,[e("button",{onClick:b,class:"absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all"},[n(s(N),{class:"h-6 w-6"})]),e("h2",_e,a(p.value?"Modifier le matériel":"Ajouter un matériel"),1),t[14]||(t[14]=e("p",{class:"text-gray-500 text-sm mb-8"},"Remplissez les informations essentielles de l'équipement.",-1)),e("form",{onSubmit:O(Q,["prevent"]),class:"space-y-6"},[e("div",null,[t[8]||(t[8]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Nom de l'équipement",-1)),e("div",Ce,[n(s(S),{class:"h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"}),f(e("input",{"onUpdate:modelValue":t[0]||(t[0]=o=>s(i).nom=o),type:"text",placeholder:"Ex: Vidéoprojecteur Epson EB-W06",class:"w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm",required:""},null,512),[[q,s(i).nom]])]),s(i).errors.nom?(l(),c("p",$e,a(s(i).errors.nom),1)):u("",!0)]),e("div",null,[t[9]||(t[9]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Numéro de Série / Tag",-1)),e("div",je,[n(s(K),{class:"h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"}),f(e("input",{"onUpdate:modelValue":t[1]||(t[1]=o=>s(i).serie=o),type:"text",placeholder:"Ex: SN-2024-001",class:"w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm"},null,512),[[q,s(i).serie]])])]),e("div",Me,[e("div",null,[t[11]||(t[11]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"État Physique",-1)),f(e("select",{"onUpdate:modelValue":t[2]||(t[2]=o=>s(i).etat=o),class:"w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none"},[...t[10]||(t[10]=[e("option",{value:"bon"},"Bon",-1),e("option",{value:"endommagé"},"Endommagé",-1),e("option",{value:"hors_service"},"Hors Service",-1)])],512),[[E,s(i).etat]])]),e("div",null,[t[13]||(t[13]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Disponibilité",-1)),f(e("select",{"onUpdate:modelValue":t[3]||(t[3]=o=>s(i).status=o),class:"w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none"},[...t[12]||(t[12]=[e("option",{value:"disponible"},"Disponible",-1),e("option",{value:"preté"},"Prêté",-1),e("option",{value:"maintenance"},"Maintenance",-1)])],512),[[E,s(i).status]])])]),e("div",qe,[e("button",{type:"button",onClick:b,class:"flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"}," Annuler "),e("button",{type:"submit",disabled:s(i).processing,class:"flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all disabled:opacity-50"},a(p.value?"Enregistrer les modifications":"Ajouter à l'inventaire"),9,Ee)])],32)])])):u("",!0),y.value?(l(),c("div",Se,[e("div",Ne,[e("button",{onClick:j,class:"absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all"},[n(s(N),{class:"h-6 w-6"})]),t[17]||(t[17]=e("h2",{class:"text-2xl font-black text-gray-900 tracking-tight mb-2"},"QR Code du Matériel",-1)),t[18]||(t[18]=e("p",{class:"text-gray-500 text-sm mb-6"},"Collez ce code sur l'équipement pour l'identifier facilement.",-1)),d.value?(l(),c("div",Ae,[e("div",Ve,[e("img",{src:`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${d.value.uuid}`,alt:d.value.nom,class:"w-56 h-56 mx-auto border-4 border-white rounded-2xl shadow-sm"},null,8,ze),t[15]||(t[15]=e("div",{class:"absolute inset-0 flex items-center justify-center pointer-events-none"},[e("div",{class:"bg-white p-1 rounded-xl shadow-md border border-slate-100 flex items-center justify-center"},[e("img",{src:"/images/logo-cre.png",alt:"CRE Logo",class:"w-12 h-12 object-contain rounded-lg"})])],-1))]),e("div",null,[e("h3",Qe,a(d.value.nom),1),e("p",Ue,"UUID: "+a(d.value.uuid),1),d.value.serie?(l(),c("span",De," S/N: "+a(d.value.serie),1)):u("",!0)]),e("div",Re,[e("button",{type:"button",onClick:j,class:"flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"}," Fermer "),e("button",{type:"button",onClick:A,class:"flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-2"},[n(s(_),{class:"h-5 w-5"}),t[16]||(t[16]=m(" Imprimer le QR ",-1))])])])):u("",!0)])])):u("",!0)]),_:1})],64))}};export{We as default};
