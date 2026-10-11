document.addEventListener("DOMContentLoaded",()=>{
    const b=document.body;
    const t=document.createElement("button");
    t.textContent="☾ Dark";
    t.className="js-theme";
    b.append(t);

    t.onclick=()=>{
        b.classList.toggle("dark-mode");
        t.textContent=b.classList.contains("dark-mode")?"Light":"Dark";
    };

    const m=document.querySelector("nav");
    if(m){
        const h=document.createElement("button");
        h.textContent="☰";
        h.className="js-menu";
        m.prepend(h);

        h.onclick=()=>m.classList.toggle("open");
    }
});