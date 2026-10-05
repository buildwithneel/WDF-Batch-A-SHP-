document.addEventListener("DOMContentLoaded",()=>{
    const b=document.body;
    const n=document.querySelector("nav");

    if(n){
        const m=document.createElement("button");
        m.textContent="☰";
        m.className="js-menu";
        n.prepend(m);
        m.onclick=()=>n.classList.toggle("open");
    }

    const t=document.createElement("button");
    t.textContent="Dark";
    t.className="js-theme";
    b.append(t);

    t.onclick=()=>{
        b.classList.toggle("dark-mode");
        t.textContent=b.classList.contains("dark-mode")?"Light":"Dark";
    };

    const banner=document.createElement("div");
    banner.className="js-banner";
    banner.textContent="Welcome to StudentHub";

    const x=document.createElement("button");
    x.textContent="×";
    x.onclick=()=>banner.remove();
    banner.append(x);
    b.prepend(banner);
});