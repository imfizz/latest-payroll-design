// review modal exit btn
let exitModalReview = document.querySelector("#exit-modal-review")
exitModalReview.addEventListener('click', e => {
    let reviewModal = document.querySelector('.modal-review');
    reviewModal.style.display = "none";
})

// statistics modal exit btn
let exitModalStatistics = document.querySelector("#exit-modal-statistics")
exitModalStatistics.addEventListener('click', e => {
    let statisticsModal = document.querySelector('.modal-statistics');
    statisticsModal.style.display = "none";
})

// company modal exit btn
let exitModalCompany = document.querySelector("#exit-modal-company")
exitModalCompany.addEventListener('click', e => {
    let companyModal = document.querySelector('.modal-company');
    companyModal.style.display = "none";
})

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

// approverequest modal exit btn
let exitModalApproveRequest = document.querySelector("#exit-modal-approverequest")
exitModalApproveRequest.addEventListener('click', e => {
    let approverequestModal = document.querySelector('.modal-approverequest');
    approverequestModal.style.display = "none";
})

