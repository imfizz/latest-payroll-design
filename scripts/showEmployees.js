// editguard modal exit btn
let exitModalEditGuard = document.querySelector("#exit-modal-editguard")
exitModalEditGuard.addEventListener('click', e => {
    let editguardModal = document.querySelector('.modal-editguard');
    editguardModal.style.display = "none";
})

// deleteguard modal exit btn
let exitModalDeleteGuard = document.querySelector("#exit-modal-deleteguard")
exitModalDeleteGuard.addEventListener('click', e => {
    let deleteguardModal = document.querySelector('.modal-deleteguard');
    deleteguardModal.style.display = "none";
})