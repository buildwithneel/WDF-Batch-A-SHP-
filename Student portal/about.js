document.addEventListener("DOMContentLoaded",()=>{
    const b=document.body;
    const t=document.createElement("button");
    t.textContent="Dark";
    t.className="js-theme";
    b.append(t);

    t.onclick=()=>{
        b.classList.toggle("dark-mode");
        t.textContent=b.classList.contains("dark-mode")?"Light":"Dark";
    };

    document.querySelectorAll("img").forEach(i=>{
        i.addEventListener("click",()=>{
            const m=document.createElement("div");
            m.className="js-modal";
            m.innerHTML='<div class="js-modal-box"><img src="'+i.src+'"><button>Close</button></div>';
            b.append(m);

            m.querySelector("button").onclick=()=>m.remove();

            m.onclick=e=>{
                if(e.target===m)m.remove();
            };
        });
    });
});