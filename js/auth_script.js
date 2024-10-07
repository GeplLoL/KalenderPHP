function disableRegBtn(){
    document.getElementById('reg-btn').disabled=true;

}
function disableLoginBtn(){
    document.getElementById('login-btn').disabled=true;

}
function loginFieldsValidation(){
    let email  =document.getElementById('login-email');
    let pass  =document.getElementById('login-pass');

    let btn=        document.getElementById('login-btn');

    if(email.value.trim() !== "" && pass.value.trim()!== "" ){
        btn.disabled=false;
    }
    else {
        btn.disabled=true;

    }
}
function registerFieldsValidation(){

    let name=document.getElementById('reg-name');
    let email =document.getElementById('reg-email');

    let pass1=document.getElementById('reg-pass');
    let pass2=document.getElementById('reg-pass-confirm');


    let btn=document.getElementById('reg-btn');

    if(name.value.trim() !== "" &&
        email.value.trim()!== ""&&
        pass1.value.trim()!== "" &&
        pass2.value.trim()!== "" &&
        pass1.value.trim() ===pass2.value.trim()){
        btn.disabled=false;
    }
    else {
        btn.disabled=true;

    }
}
 function displayErrorMessage(error) {
            const errorDiv = document.getElementById('error-div');
            errorDiv.innerText = "Viga: "+error;

        }