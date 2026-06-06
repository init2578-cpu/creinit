import{T as L,a as l,f as n,u as s,Z as H,w as W,b as e,o as a,i as u,l as d,F as f,m as Y,t as c,n as $,y as q,x as Z,k as g,v as E,O as A,E as J,N as D,p as h}from"./vendor-DmkAW-zK.js";import{_ as K}from"./AuthenticatedLayout-Wc4Sm6Y1.js";import{ag as w,j as X,ah as U,z as ee,H as te,v as N,k as re,A as se,ai as oe,s as ie,d as V,n as ae,S as Q,aj as le}from"./heroicons-0e43woc5.js";import"./axios-42ANG6Sg.js";import"./lodash-sVzdDwke.js";import"./chartjs-BznfGPhk.js";/* empty css                                                                     */import"./_plugin-vue_export-helper-DlAUqK2U.js";const ne={class:"max-w-7xl mx-auto py-8 px-4"},de={class:"mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4"},ce={class:"flex items-center gap-3"},pe={class:"bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden"},ue={class:"w-full text-left border-collapse"},ge={class:"divide-y divide-gray-50"},me={class:"px-8 py-5"},xe={class:"flex items-center gap-4"},be={class:"h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center relative"},fe={key:0,class:"absolute -top-2 -right-2 bg-gray-900 text-white p-1 rounded-full shadow-md",title:"Matériel masqué"},he={class:"font-black text-gray-900 leading-tight flex items-center gap-2"},ye={key:0,class:"px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] uppercase tracking-wider"},ve={key:1,class:"px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded text-[9px] uppercase tracking-wider border border-amber-200"},we=["onClick"],ke={class:"text-[10px] font-mono font-bold uppercase underline decoration-dashed"},_e={key:1,class:"flex items-center gap-1.5 mt-1 text-amber-500"},Ce={class:"px-8 py-5"},$e={class:"flex flex-col gap-1"},qe={class:"px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black tracking-tight border border-gray-200 inline-block w-max"},Ee={key:0,class:"flex items-center gap-1 mt-1 text-slate-500"},je={class:"text-[10px] font-bold"},Me={class:"px-8 py-5 text-center"},Se={class:"px-8 py-5 text-center"},ze={class:"flex flex-col items-center gap-1"},Ae=["title"],De=["title"],Ue={class:"px-8 py-5"},Ne={class:"flex flex-col gap-1"},Ve={class:"text-xs text-gray-900 font-black"},Qe={class:"text-[10px] text-gray-400 font-bold"},Re={class:"px-8 py-5 text-right"},Ie={class:"flex justify-end gap-2 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"},Te=["onClick"],Be=["onClick"],Pe=["onClick"],Oe=["onClick"],Fe=["onClick"],Ge={key:0},Le={key:0,class:"fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"},He={class:"bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl relative"},We={class:"text-2xl font-black text-gray-900 tracking-tight mb-2"},Ye={class:"relative"},Ze={key:0,class:"text-xs text-red-500 mt-1.5 font-bold ml-1"},Je={class:"grid grid-cols-2 gap-4"},Ke={class:"relative"},Xe={class:"relative"},et={key:0,class:"text-xs text-red-500 mt-1.5 font-bold ml-1"},tt={class:"grid grid-cols-2 gap-4"},rt={key:0,class:"flex items-center gap-3 p-4 bg-gray-50 rounded-[1.25rem] border border-gray-100"},st={class:"relative inline-flex items-center cursor-pointer"},ot={class:"flex gap-4 pt-4"},it=["disabled"],at={key:1,class:"fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"},lt={class:"bg-white w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl relative text-center"},nt={key:0,class:"space-y-6"},dt={class:"p-4 bg-slate-50 rounded-[2rem] inline-block relative"},ct=["src","alt"],pt={class:"text-xs text-gray-400 font-mono mt-1 uppercase font-bold"},ut={key:0,class:"inline-block mt-2 px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black border border-gray-200"},gt={class:"flex gap-4 pt-2"},kt={__name:"AssetsIndex",props:{assets:Array},setup(m){const k=m,y=h(!1),x=h(!1),_=h(null),C=h(!1),p=h(null);function j(o){p.value=o,C.value=!0}function M(){C.value=!1,p.value=null}function R(){if(!p.value)return;const o=p.value,t=window.open("","_blank");t.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>QR Code - ${o.nom}</title>
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
                        <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${o.uuid}" />
                        <div class="logo-overlay">
                            <img src="/images/logo-cre.png" alt="CRE Logo" />
                        </div>
                    </div>
                    <p>UUID: ${o.uuid.substring(0,8).toUpperCase()}</p>
                    ${o.serie?`<div class="serial">S/N: ${o.serie}</div>`:""}
                </div>
            </body>
        </html>
    `),t.document.close()}function I(){if(!k.assets||k.assets.length===0)return;const o=window.open("","_blank");let t="";k.assets.forEach(r=>{t+=`
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
        `}),o.document.write(`
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
    `),o.document.close()}const i=L({nom:"",serie:"",emplacement:"",etat:"bon",status:"disponible",is_hidden:!1});function T(){x.value=!1,_.value=null,i.reset(),i.clearErrors(),y.value=!0}function B(o){x.value=!0,_.value=o,i.clearErrors(),i.nom=o.nom,i.serie=o.serie||"",i.emplacement=o.emplacement||"",i.etat=o.etat,i.status=o.status,i.is_hidden=o.is_hidden,y.value=!0}function v(){y.value=!1}function P(){x.value?i.put(route("assets.update",_.value.id),{onSuccess:()=>v()}):i.post(route("assets.store"),{onSuccess:()=>v()})}function S(o){confirm("Êtes-vous sûr de vouloir supprimer ce matériel ? Cette action est irréversible.")&&D.delete(route("assets.destroy",o))}function O(o){confirm("Voulez-vous valider l'ajout de ce matériel dans l'inventaire ?")&&D.patch(route("assets.approve",o))}const F=o=>{switch(o){case"disponible":return"bg-green-50 text-green-600";case"preté":return"bg-blue-50 text-blue-600";case"maintenance":return"bg-amber-50 text-amber-600";default:return"bg-gray-50 text-gray-600"}},G=o=>{switch(o){case"bon":return"bg-emerald-50 text-emerald-700 border-emerald-100";case"endommagé":return"bg-orange-50 text-orange-700 border-orange-100";case"hors_service":return"bg-red-50 text-red-700 border-red-100";default:return"bg-gray-50 text-gray-700 border-gray-100"}};return(o,t)=>(a(),l(f,null,[n(s(H),{title:"Gestion du Matériel"}),n(K,null,{default:W(()=>[e("div",ne,[e("header",de,[t[8]||(t[8]=e("div",null,[e("h1",{class:"text-3xl font-black text-gray-900 tracking-tight"},"Gestion du Matériel"),e("p",{class:"text-gray-500 mt-1"},"Inventaire physique et suivi de l'état des équipements.")],-1)),e("div",ce,[m.assets&&m.assets.length>0?(a(),l("button",{key:0,onClick:I,class:"flex items-center gap-2 px-6 py-3 bg-white text-slate-700 border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm"},[n(s(w),{class:"h-5 w-5 text-slate-500"}),t[6]||(t[6]=u(" Imprimer tous les QR Codes ",-1))])):d("",!0),e("button",{onClick:T,class:"flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all group active:scale-95"},[n(s(X),{class:"h-5 w-5 group-hover:rotate-90 transition-transform duration-300"}),t[7]||(t[7]=u(" Nouveau Matériel ",-1))])])]),e("div",pe,[e("table",ue,[t[11]||(t[11]=e("thead",null,[e("tr",{class:"bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest"},[e("th",{class:"px-8 py-4"},"Matériel & ID"),e("th",{class:"px-8 py-4"},"Numéro de Série"),e("th",{class:"px-8 py-4 text-center"},"État Physique"),e("th",{class:"px-8 py-4 text-center"},"Disponibilité"),e("th",{class:"px-8 py-4"},"Enregistrement"),e("th",{class:"px-8 py-4"})])],-1)),e("tbody",ge,[(a(!0),l(f,null,Y(m.assets,r=>{var z;return a(),l("tr",{key:r.id,class:"hover:bg-gray-50/50 transition-colors group"},[e("td",me,[e("div",xe,[e("div",be,[n(s(U),{class:"h-6 w-6"}),r.is_hidden?(a(),l("div",fe,[n(s(ee),{class:"h-3 w-3"})])):d("",!0)]),e("div",null,[e("p",he,[u(c(r.nom)+" ",1),r.is_hidden?(a(),l("span",ye,"Masqué")):d("",!0),r.is_approved?d("",!0):(a(),l("span",ve,"En attente"))]),r.is_approved?(a(),l("button",{key:0,onClick:b=>j(r),class:"flex items-center gap-1.5 mt-1 text-slate-400 hover:text-indigo-600 transition group/qr",title:"Afficher le QR Code"},[n(s(w),{class:"h-3.5 w-3.5"}),e("span",ke,c(r.uuid.substring(0,8)),1)],8,we)):(a(),l("div",_e,[n(s(te),{class:"h-3.5 w-3.5"}),t[9]||(t[9]=e("span",{class:"text-[10px] font-bold uppercase tracking-widest"},"En attente de validation",-1))]))])])]),e("td",Ce,[e("div",$e,[e("span",qe,c(r.serie||"N/A"),1),r.emplacement?(a(),l("div",Ee,[n(s(N),{class:"h-3.5 w-3.5"}),e("span",je,c(r.emplacement),1)])):d("",!0)])]),e("td",Me,[e("span",{class:$(["inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border",G(r.etat)])},[r.etat==="bon"?(a(),q(s(re),{key:0,class:"h-3.5 w-3.5"})):r.etat==="endommagé"?(a(),q(s(se),{key:1,class:"h-3.5 w-3.5"})):(a(),q(s(oe),{key:2,class:"h-3.5 w-3.5"})),u(" "+c(r.etat.replace("_"," ")),1)],2)]),e("td",Se,[e("div",ze,[e("span",{class:$(["inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider",F(r.status)])},[e("span",{class:$(["h-1.5 w-1.5 rounded-full",r.status==="disponible"?"bg-green-600":r.status==="preté"?"bg-blue-600":"bg-amber-600"])},null,2),u(" "+c(r.status),1)],2),r.status==="preté"&&r.borrower?(a(),l("p",{key:0,class:"text-[10px] text-slate-500 font-bold max-w-[150px] truncate",title:`Emprunteur: ${r.borrower.name}`}," Par : "+c(r.borrower.name),9,Ae)):d("",!0),r.status==="preté"&&r.giver?(a(),l("p",{key:1,class:"text-[9px] text-slate-400 font-medium max-w-[150px] truncate",title:`Donné par: ${r.giver.name}`}," Donné par : "+c(r.giver.name),9,De)):d("",!0)])]),e("td",Ue,[e("div",Ne,[e("span",Ve,c(((z=r.registered_by)==null?void 0:z.name)||"Système"),1),e("span",Qe,c(r.created_at),1)])]),e("td",Re,[e("div",Ie,[!r.is_approved&&o.$page.props.auth.user.roles.includes("Directeur")?(a(),l(f,{key:0},[e("button",{onClick:b=>O(r.id),class:"p-2 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm shadow-emerald-50",title:"Approuver l'ajout"},[n(s(ie),{class:"h-5 w-5"})],8,Te),e("button",{onClick:b=>S(r.id),class:"p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm shadow-red-50",title:"Refuser et supprimer"},[n(s(V),{class:"h-5 w-5"})],8,Be)],64)):r.is_approved?(a(),l(f,{key:1},[e("button",{onClick:b=>j(r),class:"p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50",title:"Voir le QR Code"},[n(s(w),{class:"h-5 w-5"})],8,Pe),o.$page.props.auth.user.roles.includes("Directeur")?(a(),l(f,{key:0},[e("button",{onClick:b=>B(r),class:"p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50",title:"Modifier"},[n(s(ae),{class:"h-5 w-5"})],8,Oe),e("button",{onClick:b=>S(r.id),class:"p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm shadow-red-50",title:"Supprimer"},[n(s(V),{class:"h-5 w-5"})],8,Fe)],64)):d("",!0)],64)):d("",!0)])])])}),128)),m.assets.length===0?(a(),l("tr",Ge,[...t[10]||(t[10]=[e("td",{colspan:"6",class:"px-8 py-12 text-center text-gray-400 italic font-medium"}," Aucun matériel enregistré dans l'inventaire. ",-1)])])):d("",!0)])])])]),y.value?(a(),l("div",Le,[e("div",He,[e("button",{onClick:v,class:"absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all"},[n(s(Q),{class:"h-6 w-6"})]),e("h2",We,c(x.value?"Modifier le matériel":"Ajouter un matériel"),1),t[21]||(t[21]=e("p",{class:"text-gray-500 text-sm mb-8"},"Remplissez les informations essentielles de l'équipement.",-1)),e("form",{onSubmit:Z(P,["prevent"]),class:"space-y-6"},[e("div",null,[t[12]||(t[12]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Nom de l'équipement",-1)),e("div",Ye,[n(s(U),{class:"h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"}),g(e("input",{"onUpdate:modelValue":t[0]||(t[0]=r=>s(i).nom=r),type:"text",placeholder:"Ex: Vidéoprojecteur Epson EB-W06",class:"w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm",required:""},null,512),[[E,s(i).nom]])]),s(i).errors.nom?(a(),l("p",Ze,c(s(i).errors.nom),1)):d("",!0)]),e("div",Je,[e("div",null,[t[13]||(t[13]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Numéro de Série / Tag",-1)),e("div",Ke,[n(s(le),{class:"h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"}),g(e("input",{"onUpdate:modelValue":t[1]||(t[1]=r=>s(i).serie=r),type:"text",placeholder:"Ex: SN-2024-001",class:"w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm"},null,512),[[E,s(i).serie]])])]),e("div",null,[t[14]||(t[14]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Emplacement",-1)),e("div",Xe,[n(s(N),{class:"h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"}),g(e("input",{"onUpdate:modelValue":t[2]||(t[2]=r=>s(i).emplacement=r),type:"text",placeholder:"Ex: Armoire 2, Salle B",class:"w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm",required:""},null,512),[[E,s(i).emplacement]])]),s(i).errors.emplacement?(a(),l("p",et,c(s(i).errors.emplacement),1)):d("",!0)])]),e("div",tt,[e("div",null,[t[16]||(t[16]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"État Physique",-1)),g(e("select",{"onUpdate:modelValue":t[3]||(t[3]=r=>s(i).etat=r),class:"w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none"},[...t[15]||(t[15]=[e("option",{value:"bon"},"Bon",-1),e("option",{value:"endommagé"},"Endommagé",-1),e("option",{value:"hors_service"},"Hors Service",-1)])],512),[[A,s(i).etat]])]),e("div",null,[t[18]||(t[18]=e("label",{class:"block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1"},"Disponibilité",-1)),g(e("select",{"onUpdate:modelValue":t[4]||(t[4]=r=>s(i).status=r),class:"w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none"},[...t[17]||(t[17]=[e("option",{value:"disponible"},"Disponible",-1),e("option",{value:"preté"},"Prêté",-1),e("option",{value:"maintenance"},"Maintenance",-1)])],512),[[A,s(i).status]])])]),o.$page.props.auth.user.roles.includes("Directeur")?(a(),l("div",rt,[e("label",st,[g(e("input",{type:"checkbox","onUpdate:modelValue":t[5]||(t[5]=r=>s(i).is_hidden=r),class:"sr-only peer"},null,512),[[J,s(i).is_hidden]]),t[19]||(t[19]=e("div",{class:"w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"},null,-1))]),t[20]||(t[20]=e("div",null,[e("p",{class:"text-sm font-bold text-gray-900"},"Masquer ce matériel"),e("p",{class:"text-xs text-gray-500"},"Rend le matériel invisible pour les autres utilisateurs.")],-1))])):d("",!0),e("div",ot,[e("button",{type:"button",onClick:v,class:"flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"}," Annuler "),e("button",{type:"submit",disabled:s(i).processing,class:"flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all disabled:opacity-50"},c(x.value?"Enregistrer les modifications":"Ajouter à l'inventaire"),9,it)])],32)])])):d("",!0),C.value?(a(),l("div",at,[e("div",lt,[e("button",{onClick:M,class:"absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all"},[n(s(Q),{class:"h-6 w-6"})]),t[24]||(t[24]=e("h2",{class:"text-2xl font-black text-gray-900 tracking-tight mb-2"},"QR Code du Matériel",-1)),t[25]||(t[25]=e("p",{class:"text-gray-500 text-sm mb-6"},"Collez ce code sur l'équipement pour l'identifier facilement.",-1)),p.value?(a(),l("div",nt,[e("div",dt,[e("img",{src:`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${p.value.uuid}`,alt:p.value.nom,class:"w-56 h-56 mx-auto border-4 border-white rounded-2xl shadow-sm"},null,8,ct),t[22]||(t[22]=e("div",{class:"absolute inset-0 flex items-center justify-center pointer-events-none"},[e("div",{class:"bg-white p-1 rounded-xl shadow-md border border-slate-100 flex items-center justify-center"},[e("img",{src:"/images/logo-cre.png",alt:"CRE Logo",class:"w-12 h-12 object-contain rounded-lg"})])],-1))]),e("div",null,[e("p",pt,"UUID: "+c(p.value.uuid),1),p.value.serie?(a(),l("span",ut," S/N: "+c(p.value.serie),1)):d("",!0)]),e("div",gt,[e("button",{type:"button",onClick:M,class:"flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"}," Fermer "),e("button",{type:"button",onClick:R,class:"flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-2"},[n(s(w),{class:"h-5 w-5"}),t[23]||(t[23]=u(" Imprimer le QR ",-1))])])])):d("",!0)])])):d("",!0)]),_:1})],64))}};export{kt as default};
