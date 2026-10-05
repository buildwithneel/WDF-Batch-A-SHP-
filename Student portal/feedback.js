document.addEventListener("DOMContentLoaded",()=>{
    const f=document.querySelector("form");

    if(f){
        f.addEventListener("submit",e=>{
            alert("Thank you for your feedback!");
            e.preventDefault();
        });
    }
});