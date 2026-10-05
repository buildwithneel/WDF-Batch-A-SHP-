document.addEventListener("DOMContentLoaded",()=>{
    const b=document.body;
    const n=document.querySelector("nav");
    const h=document.querySelector("header");
    const s=document.createElement("style");

    s.textContent=`#js-menu{display:none;background:#1565c0;color:#fff;border:0;border-radius:6px;padding:9px 13px;font-size:22px;cursor:pointer}#js-nav{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.js-theme{background:#fff;color:#0d47a1;border:0;border-radius:6px;padding:8px 12px;cursor:pointer}.js-banner{position:fixed;top:0;left:0;right:0;z-index:9999;background:#1565c0;color:#fff;padding:12px 45px 12px 18px;text-align:center;font-weight:500}.js-banner button{position:absolute;right:15px;top:8px;background:transparent;border:0;color:#fff;font-size:22px;cursor:pointer}.js-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:10000;align-items:center;justify-content:center}.js-modal.show{display:flex}.js-modal-box{width:min(90%,500px);background:#fff;border-radius:12px;padding:30px;box-shadow:0 10px 35px rgba(0,0,0,.25);text-align:center}.js-modal-box button{background:#1565c0;color:#fff;border:0;border-radius:6px;padding:10px 20px;cursor:pointer}.js-slider{position:relative;width:min(100%,850px);margin:25px auto;overflow:hidden;border-radius:12px}.js-slide{display:none;width:100%;height:300px;object-fit:cover}.js-slide.active{display:block}.js-prev,.js-next{position:absolute;top:50%;transform:translateY(-50%);border:0;background:rgba(0,0,0,.5);color:#fff;font-size:24px;padding:8px 14px;cursor:pointer}.js-prev{left:10px}.js-next{right:10px}.dark-mode{background:#121826!important;color:#e7eaf0!important}.dark-mode header,.dark-mode footer{background:#0b1220!important}.dark-mode section,.dark-mode .js-modal-box{background:#1b2433!important;color:#e7eaf0!important}.dark-mode p,.dark-mode label,.dark-mode h1,.dark-mode h2,.dark-mode h3{color:#e7eaf0!important}.dark-mode input,.dark-mode select,.dark-mode textarea{background:#111827!important;color:#fff!important;border-color:#475569!important}.dark-mode a{color:#93c5fd}.dark-mode .js-theme{background:#1b2433;color:#fff}@media(max-width:900px){#js-menu{display:block}#js-nav{display:none;flex-direction:column;align-items:stretch}#js-nav.open{display:flex}#js-nav a{display:block;text-align:center}.js-slider img.js-slide{height:220px}}`;

    document.head.appendChild(s);

    if(h&&n){
        const m=document.createElement("button");
        m.id="js-menu";
        m.textContent="☰";
        n.prepend(m);

        const wrap=document.createElement("div");
        wrap.id="js-nav";

        Array.from(n.querySelectorAll("a")).forEach(a=>wrap.appendChild(a));
        n.appendChild(wrap);

        m.addEventListener("click",()=>wrap.classList.toggle("open"));

        const t=document.createElement("button");
        t.className="js-theme";

        t.addEventListener("click",()=>{
            b.classList.toggle("dark-mode");
            const d=b.classList.contains("dark-mode");
            localStorage.setItem("studenthub-theme",d?"dark":"light");
            t.textContent=d?"☀ Light":"☾ Dark";
        });

        if(localStorage.getItem("studenthub-theme")==="dark"){
            b.classList.add("dark-mode");
        }

        t.textContent=b.classList.contains("dark-mode")?"☀ Light":"☾ Dark";
        wrap.appendChild(t);
    }

    const banner=document.createElement("div");
    banner.className="js-banner";
    banner.append("Welcome to StudentHub — stay updated with academics, events and campus activities.");

    const close=document.createElement("button");
    close.textContent="×";
    close.addEventListener("click",()=>banner.remove());
    banner.appendChild(close);
    b.prepend(banner);

    const modal=document.createElement("div");
    modal.className="js-modal";
    modal.innerHTML='<div class="js-modal-box"><h2>StudentHub</h2><p id="js-modal-text">Welcome to StudentHub.</p><button id="js-modal-close">Close</button></div>';
    b.appendChild(modal);

    document.getElementById("js-modal-close").addEventListener("click",()=>modal.classList.remove("show"));

    modal.addEventListener("click",e=>{
        if(e.target===modal)modal.classList.remove("show");
    });

    document.addEventListener("keydown",e=>{
        if(e.key==="Escape")modal.classList.remove("show");
    });

    document.querySelectorAll('a[href="#"]').forEach(a=>{
        a.addEventListener("click",e=>{
            e.preventDefault();
            document.getElementById("js-modal-text").textContent=a.textContent.trim()+" is currently available in the StudentHub demo.";
            modal.classList.add("show");
        });
    });

    document.querySelectorAll("details").forEach(d=>{
        d.addEventListener("toggle",()=>{
            if(d.open){
                document.querySelectorAll("details").forEach(x=>{
                    if(x!==d)x.removeAttribute("open");
                });
            }
        });
    });

    const main=document.querySelector("main");

    if(main){
        const imgs=Array.from(main.querySelectorAll("img"));

        if(imgs.length>=3){
            const slider=document.createElement("div");
            slider.className="js-slider";

            imgs.slice(0,6).forEach((img,i)=>{
                const x=img.cloneNode(true);
                x.className="js-slide"+(i===0?" active":"");
                slider.appendChild(x);
            });

            const p=document.createElement("button");
            const nx=document.createElement("button");

            p.className="js-prev";
            p.textContent="‹";
            nx.className="js-next";
            nx.textContent="›";
            slider.append(p,nx);
            main.insertBefore(slider,main.firstChild);

            let i=0;
            const slides=Array.from(slider.querySelectorAll(".js-slide"));

            const show=k=>{
                slides[i].classList.remove("active");
                i=(k+slides.length)%slides.length;
                slides[i].classList.add("active");
            };

            p.addEventListener("click",()=>show(i-1));
            nx.addEventListener("click",()=>show(i+1));
            setInterval(()=>show(i+1),5000);
        }
    }
});