document.addEventListener("DOMContentLoaded",()=>{const f=document.querySelector("form");
    if(f)f.addEventListener("submit",e=>{const p=document.createElement("div");
        p.className="js-modal";
        p.innerHTML='<div class="js-modal-box"><h2>Registration</h2><p>Your registration form is ready to submit.</p><button>Close</button></div>';
    document.body.append(p);
    p.querySelector("button").onclick=()=>p.remove();
    e.preventDefault()});
});