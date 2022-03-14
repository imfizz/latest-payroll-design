let exitAddMore = document.querySelector('#exit-addmore-modal');
let exitAddMoreModal = document.querySelector('.addmore-modal');

exitAddMore.addEventListener('click', e =>{
    exitAddMoreModal.style.display = "none";
});

// open modal
let addmoreEmp = document.querySelector('.addmore-emp');
addmoreEmp.addEventListener('click', e => {
    let addMoreModal = document.querySelector('.addmore-modal');
    addMoreModal.style.display = 'block';
});

function populateLocation(e){
    let opt = e.selectedIndex;
    let optLocValue = e.options[opt].dataset.loc;
    let optIdValue = e.options[opt].dataset.comid;

    // position
    let puthere = document.querySelectorAll('.puthere');
    let selectsTag = Object.values(puthere);

    // price
    let puthere2 = document.querySelectorAll('.puthere2');
    let priceTag = Object.values(puthere2);

    if(opt == 0){
        // remove all options inside select
        selectsTag.forEach(sel => {
            sel.innerHTML = "<option value=''>Select Position</option>";
        })

        priceTag.forEach(price => {
            price.value = '';
        })

    } else {
        let optPosValue = e.options[opt].dataset.pos;
        let optPriValue = e.options[opt].dataset.price;

        // position and price convert to array;
        let positions = optPosValue.split(",");
        let prices = optPriValue.split(",");

        positions.pop();
        prices.pop();
        
        console.log(positions);
        selectsTag.forEach(sel => {
            for(let i = 1; i < selectsTag.length; i++){
                sel.innerHTML = "<option data-posprice='' value=''>Select Position</option>";
            }
        });

        priceTag.forEach(price => {
            price.value = '';
        });

        selectsTag.forEach(sel=>{
            for(let i = 0; i < positions.length; i++){
                let option = document.createElement('option');
                option.setAttribute('data-posprice', prices[i]);
                option.value = positions[i];
                option.innerText = positions[i];
                sel.appendChild(option);
            }
        });
    }

    let location = document.querySelector("#location");
    location.value = optLocValue;

    // remove undefined values
    if(optLocValue == undefined){
        location.value = '';
    }
}

function setPrice(sel){
    let myTd = sel.parentElement; // td
    let puthere2 = myTd.querySelector('.puthere2');

    // set input price value
    let selIndex = sel.selectedIndex;
    puthere2.value = sel.options[selIndex].dataset.posprice
}

function removeMe(me){
    let currentUrl = window.location.href;

    let myId = me.dataset.deleteid; // 22
    let lengthIdIndex = myId.length; // 2

    let startingPoint = currentUrl.indexOf("="); // find my index
    let endingPoint = currentUrl.length;

    let outputMe = currentUrl.substr(startingPoint + 1, endingPoint); // all ids
    let outputArray = outputMe.split(',');

    // remove single id
    let filteredArray = outputArray.filter( arr =>  arr != myId );

    // current filename
    let fileName = "selectedGuards.php?ids=";
    filteredArray.forEach(arr => {
        fileName += arr +',';
    });

    if(fileName.charAt(fileName.length - 1) == ','){
        fileName = fileName.substr(0, fileName.length - 1);
    }

    window.location.replace(fileName);
}

document.addEventListener('DOMContentLoaded', runMe);

function runMe(){
    let currentUrl = window.location.href;
    
    let startingPoint = currentUrl.indexOf("="); // find my index
    let endingPoint = currentUrl.length;

    let myIds = currentUrl.substr(startingPoint + 1, endingPoint);
    
    let myIdsArray = myIds.split(',');

    let dodelete = document.querySelectorAll('.doDelete');
    let dodeleteArray = Object.values(dodelete);

    dodeleteArray.forEach(dodel => {
        let empIdDelete = dodel.dataset.empiddelete;

        // 0, 1, 2
        for(let i = 0; i < myIdsArray.length; i++){
            if(empIdDelete == myIdsArray[i]){
                dodel.remove();
            }
        }
    });
}

function redirectAgain(){
    let ids = document.querySelector('#ids');
    let currentUrl = window.location.href + "," +ids.value;

    window.location.assign(currentUrl);
}


// for expiration date
function setYear(iyear)
{
    let imonth = document.querySelector('#month');
    let iday = document.querySelector('#day');

    if(iyear.selectedIndex == 2){
        imonth.value = '';
        iday.value = '';

        // disable
        imonth.disabled = true;
        iday.disabled = true;
    } else {
        // enable
        imonth.disabled = false;
        iday.disabled = false;
    }
}

function setMonthDay(e)
{
    let iyear = document.querySelector('#year');

    let inputId = e.getAttribute('id');
    if(e.value > 0){
        if(iyear.options[2]){
            // remove 2 in option
            iyear.removeChild(iyear.options[2]);
            iyear.required = false;
        }

        
    } else {
        // add 2 in option
        let opt = document.createElement('option');
        opt.value = '2';
        opt.innerText = 2;

        iyear.appendChild(opt);
        iyear.required = true;
    }
}



let arrayIds = [];
function setVal(e, id){
    let ids = document.querySelector('#ids');

    if(e.checked){
        if(ids.value == ''){
            arrayIds.push(id);
            ids.value += arrayIds;
        } else {

            if(!arrayIds.includes(id)){
                arrayIds.push(id);
                ids.value = arrayIds;
            }
        }
    }

    if(e.checked != true){
        let newArray = arrayIds.filter( arrayId => arrayId != id);
        arrayIds = newArray;
        ids.value = newArray;
    }
    console.log(arrayIds);
}