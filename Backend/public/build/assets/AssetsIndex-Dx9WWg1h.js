import{T as B,a as c,f as a,u as s,Z as P,w as O,b as e,o as n,i as m,l as p,F as E,m as F,t as l,n as C,y as $,x as G,k as h,v as M,O as S,N as L,p as x}from"./vendor-DmkAW-zK.js";import{_ as W}from"./AuthenticatedLayout-Wc4Sm6Y1.js";import{ag as y,j as H,ah as N,k as Y,A as Z,ai as J,n as K,d as X,S as z,aj as ee}from"./heroicons-0e43woc5.js";import"./axios-42ANG6Sg.js";import"./lodash-sVzdDwke.js";import"./chartjs-BznfGPhk.js";/* empty css                                                                     */import"./_plugin-vue_export-helper-DlAUqK2U.js";const te={class:"max-w-7xl mx-auto py-8 px-4"},oe={class:"mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4"},se={class:"flex items-center gap-3"},re={class:"bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden"},ie={class:"w-full text-left border-collapse"},ae={class:"divide-y divide-gray-50"},ne={class:"px-8 py-5"},le={class:"flex items-center gap-4"},de={class:"h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center"},ce={class:"font-black text-gray-900 leading-tight"},pe=["onClick"],ue={class:"text-[10px] font-mono font-bold uppercase underline decoration-dashed"},ge={class:"px-8 py-5"},me={class:"px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black tracking-tight border border-gray-200"},xe={class:"px-8 py-5 text-center"},be={class:"px-8 py-5 text-center"},fe={class:"px-8 py-5 text-xs text-gray-400 font-bold"},he={class:"px-8 py-5 text-right"},ye={class:"flex justify-end gap-2 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"},ve=["onClick"],we=["onClick"],ke=["onClick"],_e={key:0},Ce={key:0,class:"fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"},$e={class:"bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl relative"},qe={class:"text-2xl font-black text-gray-900 tracking-tight mb-2"},je={class:"relative"},Ee={key:0,class:"text-xs text-red-500 mt-1.5 font-bold ml-1"},Me={class:"relative"},Se={class:"grid grid-cols-2 gap-4"},Ne={class:"flex gap-4 pt-4"},ze=["disabled"],Ae={key:1,class:"fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"},Ue={class:"bg-white w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl relative text-center"},Qe={key:0,class:"space-y-6"},Ve={class:"p-4 bg-slate-50 rounded-[2rem] inline-block relative"},De=["src","alt"],Re={class:"text-xs text-gray-400 font-mono mt-1 uppercase font-bold"},Ie={key:0,class:"inline-block mt-2 px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black border border-gray-200"},Te={class:"flex gap-4 pt-2"},Ye={__name:"AssetsIndex",props:{assets:Array},setup(u){const v=u,b=x(!1),g=x(!1),w=x(null),k=x(!1),d=x(null);function q(r){d.value=r,k.value=!0}function j(){k.value=!1,d.value=null}function A(){if(!d.value)return;const r=d.value,t=window.open("","_blank");t.document.write(`
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
                    <p>UUID: ${r.uuid.substring(0,8).toUpperCase()}</p>
                    ${r.serie?`<div class="serial">S/N: ${r.serie}</div>`:""}
                </div>
            </body>
        </html>
    `),t.document.close()}function U(){if(!v.assets||v.assets.length===0)return;const r=window.open("","_blank");let t="";v.assets.forEach(o=>{t+=`
            <div class="card">
                <div class="qr-container">
                    <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${o.uuid}" />
                    <div class="logo-overlay">
                        <img src="/images/logo-cre.png" alt="CRE Logo" />
                    </div>
                </div>
                <p>UUID: ${o.uuid.substring(0,8).toUpperCase()}</p>
                ${o.serie?`<div class="serial">S/N: ${o.serie}</div>`:""}
            </div>
        `}),r.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>QR Codes - Planche d'impression</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        margin: 20px;
                        color: #1e293b;
                        background-color: #fff;
                    }
                    .grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                        gap: 20px;
                    }
                    .card {
                        border: 2px solid #e2e8f0;
                        border-radius: 1rem;
                        padding: 1.5rem;
                        text-align: center;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                        page-break-inside: avoid;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                    }
                    .qr-container {
                        position: relative;
                        display: inline-block;
                    }
                    img.qr {
                        width: 130px;
                        height: 130px;
                        border: 3px solid #f1f5f9;
                        border-radius: 0.75rem;
                    }
                    .logo-overlay {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background-color: white;
                        padding: 3px;
                        border-radius: 6px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        border: 1px solid #e2e8f0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .logo-overlay img {
                        width: 26px;
                        height: 26px;
                        object-fit: contain;
                        border-radius: 2px;
                    }
                    h1 {
                        margin-top: 1rem;
                        margin-bottom: 0.25rem;
                        font-size: 0.95rem;
                        font-weight: 800;
                        word-break: break-word;
                    }
                    p {
                        font-size: 0.75rem;
                        color: #64748b;
                        margin: 0.15rem 0;
                        font-weight: 500;
                    }
                    .serial {
                        display: inline-block;
                        background-color: #f1f5f9;
                        padding: 0.15rem 0.5rem;
                        border-radius: 0.35rem;
                        font-size: 0.65rem;
                        font-weight: 700;
                        margin-top: 0.35rem;
                    }
                    @media print {
                        body {
                            margin: 0;
                        }
                        .grid {
                            gap: 15px;
                        }
                    }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="grid">
                    ${t}
                </div>
            </body>
        </html>
    `),r.document.close()}const i=B({nom:"",serie:"",etat:"bon",status:"disponible"});function Q(){g.value=!1,w.value=null,i.reset(),i.clearErrors(),b.value=!0}function V(r){g.value=!0,w.value=r,i.clearErrors(),i.nom=r.nom,i.serie=r.serie||"",i.etat=r.etat,i.status=r.status,b.value=!0}function f(){b.value=!1}function D(){g.value?i.put(route("assets.update",w.value.id),{onSuccess:()=>f()}):i.post(route("assets.store"),{onSuccess:()=>f()})}function R(r){confirm("Êtes-vous sûr de vouloir supprimer ce matériel ? Cette action est irréversible.")&&L.delete(route("assets.destroy",r))}const I=r=>{switch(r){case"disponible":return"bg-green-50 text-green-600";case"preté":return"bg-blue-50 text-blue-600";case"maintenance":return"bg-amber-50 text-amber-600";default:return"bg-gray-50 text-gray-600"}},T=r=>{switch(r){case"bon":return"bg-emerald-50 text-emerald-700 border-emerald-100";case"endommagé":return"bg-orange-50 text-orange-700 border-orange-100";case"hors_service":return"bg-red-50 text-red-700 border-red-100";default:return"bg-gray-50 text-gray-700 border-gray-100"}};return(r,t)=>(n(),c(E,null,[a(s(P),{title:"Gestion du Matériel"}),a(W,null,{default:O(()=>[e("div",te,[e("header",oe,[t[6]||(t[6]=e("div",null,[e("h1",{class:"text-3xl font-black text-gray-900 tracking-tight"},"Gestion du Matériel"),e("p",{class:"text-gray-500 mt-1"},"Inventaire physique et suivi de l'état des équipements.")],-1)),e("div",se,[u.assets&&u.assets.length>0?(n(),c("button",{key:0,onClick:U,class:"flex items-center gap-2 px-6 py-3 bg-white text-slate-700 border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm"},[a(s(y),{class:"h-5 w-5 text-slate-500"}),t[4]||(t[4]=m(" Imprimer tous les QR Codes ",-1))])):p("",!0),e("button",{onClick:Q,class:"flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all group active:scale-95"},[a(s(H),{class:"h-5 w-5 group-hover:rotate-90 transition-transform duration-300"}),t[5]||(t[5]=m(" Nouveau Matériel ",-1))])])]),e("div",re,[e("table",ie,[t[8]||(t[8]=e("thead",null,[e("tr",{class:"bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest"},[e("th",{class:"px-8 py-4"},"Matériel & ID"),e("th",{class:"px-8 py-4"},"Numéro de Série"),e("th",{class:"px-8 py-4 text-center"},"État Physique"),e("th",{class:"px-8 py-4 text-center"},"Disponibilité"),e("th",{class:"px-8 py-4"},"Ajouté le"),e("th",{class:"px-8 py-4"})])],-1)),e("tbody",ae,[(n(!0),c(E,null,F(u.assets,o=>(n(),c("tr",{key:o.id,class:"hover:bg-gray-50/50 transition-colors group"},[e("td",ne,[e("div",le,[e("div",de,[a(s(N),{class:"h-6 w-6"})]),e("div",null,[e("p",ce,l(o.nom),1),e("button",{onClick:_=>q(o),class:"flex items-center gap-1.5 mt-1 text-slate-400 hover:text-indigo-600 transition group/qr",title:"Afficher le QR Code"},[a(s(y),{class:"h-3.5 w-3.5"}),e("span",ue,l(o.uuid.substring(0,8)),1)],8,pe)])])]),e("td",ge,[e("span",me,l(o.serie||"N/A"),1)]),e("td",xe,[e("span",{class:C(["inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border",T(o.etat)])},[o.etat==="bon"?(n(),$(s(Y),{key:0,class:"h-3.5 w-3.5"})):o.etat==="endommagé"?(n(),$(s(Z),{key:1,class:"h-3.5 w-3.5"})):(n(),$(s(J),{key:2,class:"h-3.5 w-3.5"})),m(" "+l(o.etat.replace("_"," ")),1)],2)]),e("td",be,[e("span",{class:C(["inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider",I(o.status)])},[e("span",{class:C(["h-1.5 w-1.5 rounded-full",o.status==="disponible"?"bg-green-600":o.status==="preté"?"bg-blue-600":"bg-amber-600"])},null,2),m(" "+l(o.status),1)],2)]),e("td",fe,l(o.created_at),1),e("td",he,[e("div",ye,[e("button",{onClick:_=>q(o),class:"p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50",title:"Voir le QR Code"},[a(s(y),{class:"h-5 w-5"})],8,ve),e("button",{onClick:_=>V(o),class:"p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50",title:"Modifier"},[a(s(K),{class:"h-5 w-5"})],8,we),e("button",{onClick:_=>R(o.id),class:"p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm shadow-red-50",title:"Supprimer"},[a(s(X),{class:"h-5 w-5"})],8,ke)])])]))),128)),u.assets.length===0?(n(),c("tr",_e,[...t[7]||(t[7]=[e("td",{colspan:"6",class:"px-8 py-12 text-center text-gray-400 italic font-medium"}," Aucun matériel enregistré dans l'inventaire. ",-1)])])):p("",!0)])])])]),b.value?(n(),c("div",Ce,[e("div",$e,[e("button",{onClick:f,class:"absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all"},[a(s(z),{class:"h-6 w-6"})]),e("h2",qe,l(g.value?"Modifier le matériel":"Ajouter un matériel"),1),t[15]||(t[15]=e("p",{class:"text-gray-500 text-sm mb-8"},"Remplissez les informations essentielles de l'équipement.",-1)),e("form",{onSubmit:G(D,["prevent"]),class:"space-y-6"},[e("div",null,[t[9]||(t[9]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Nom de l'équipement",-1)),e("div",je,[a(s(N),{class:"h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"}),h(e("input",{"onUpdate:modelValue":t[0]||(t[0]=o=>s(i).nom=o),type:"text",placeholder:"Ex: Vidéoprojecteur Epson EB-W06",class:"w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm",required:""},null,512),[[M,s(i).nom]])]),s(i).errors.nom?(n(),c("p",Ee,l(s(i).errors.nom),1)):p("",!0)]),e("div",null,[t[10]||(t[10]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Numéro de Série / Tag",-1)),e("div",Me,[a(s(ee),{class:"h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"}),h(e("input",{"onUpdate:modelValue":t[1]||(t[1]=o=>s(i).serie=o),type:"text",placeholder:"Ex: SN-2024-001",class:"w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm"},null,512),[[M,s(i).serie]])])]),e("div",Se,[e("div",null,[t[12]||(t[12]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"État Physique",-1)),h(e("select",{"onUpdate:modelValue":t[2]||(t[2]=o=>s(i).etat=o),class:"w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none"},[...t[11]||(t[11]=[e("option",{value:"bon"},"Bon",-1),e("option",{value:"endommagé"},"Endommagé",-1),e("option",{value:"hors_service"},"Hors Service",-1)])],512),[[S,s(i).etat]])]),e("div",null,[t[14]||(t[14]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Disponibilité",-1)),h(e("select",{"onUpdate:modelValue":t[3]||(t[3]=o=>s(i).status=o),class:"w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none"},[...t[13]||(t[13]=[e("option",{value:"disponible"},"Disponible",-1),e("option",{value:"preté"},"Prêté",-1),e("option",{value:"maintenance"},"Maintenance",-1)])],512),[[S,s(i).status]])])]),e("div",Ne,[e("button",{type:"button",onClick:f,class:"flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"}," Annuler "),e("button",{type:"submit",disabled:s(i).processing,class:"flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all disabled:opacity-50"},l(g.value?"Enregistrer les modifications":"Ajouter à l'inventaire"),9,ze)])],32)])])):p("",!0),k.value?(n(),c("div",Ae,[e("div",Ue,[e("button",{onClick:j,class:"absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all"},[a(s(z),{class:"h-6 w-6"})]),t[18]||(t[18]=e("h2",{class:"text-2xl font-black text-gray-900 tracking-tight mb-2"},"QR Code du Matériel",-1)),t[19]||(t[19]=e("p",{class:"text-gray-500 text-sm mb-6"},"Collez ce code sur l'équipement pour l'identifier facilement.",-1)),d.value?(n(),c("div",Qe,[e("div",Ve,[e("img",{src:`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${d.value.uuid}`,alt:d.value.nom,class:"w-56 h-56 mx-auto border-4 border-white rounded-2xl shadow-sm"},null,8,De),t[16]||(t[16]=e("div",{class:"absolute inset-0 flex items-center justify-center pointer-events-none"},[e("div",{class:"bg-white p-1 rounded-xl shadow-md border border-slate-100 flex items-center justify-center"},[e("img",{src:"/images/logo-cre.png",alt:"CRE Logo",class:"w-12 h-12 object-contain rounded-lg"})])],-1))]),e("div",null,[e("p",Re,"UUID: "+l(d.value.uuid),1),d.value.serie?(n(),c("span",Ie," S/N: "+l(d.value.serie),1)):p("",!0)]),e("div",Te,[e("button",{type:"button",onClick:j,class:"flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"}," Fermer "),e("button",{type:"button",onClick:A,class:"flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-2"},[a(s(y),{class:"h-5 w-5"}),t[17]||(t[17]=m(" Imprimer le QR ",-1))])])])):p("",!0)])])):p("",!0)]),_:1})],64))}};export{Ye as default};
