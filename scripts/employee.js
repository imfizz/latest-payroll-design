// addguard modal exit btn
let exitModalAddGuard = document.querySelector("#exit-modal-addguard")
exitModalAddGuard.addEventListener('click', e => {
    let addguardModal = document.querySelector('.modal-addguard');
    addguardModal.style.display = "none";
})

// deleteguard modal exit btn
let exitModalDeleteGuard = document.querySelector("#exit-modal-deleteguard")
exitModalDeleteGuard.addEventListener('click', e => {
    let deleteguardModal = document.querySelector('.modal-deleteguard');
    deleteguardModal.style.display = "none";
})